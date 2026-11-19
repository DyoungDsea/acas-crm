<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderPageRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBatch;
use App\Models\OrderItemStockLot;
use App\Models\PharmacyBatch;
use App\Models\Product;
use App\Models\StockLot;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Available to every company type, so no abort_unless($company->type
// === ...) guard here. Stock drawdown is the one part that depends on
// the company: pharmacy draws from PharmacyBatch (expiry-aware, FEFO),
// the types in Company::STOCK_TRACKED_TYPES (the hair vendor) draw from
// StockLot (oldest received first, FIFO), and every other type has no
// stock table to check against yet, so allocateStock/restoreStock
// no-op for them. Company::tracksStock() is the one place that says
// which is which.
//
// No destroy() on purpose, same reasoning as Deals and
// PharmacyBatches — an order is financial history, not a draft.
// Api\OrderController does allow destroy(), but only while zero
// payments exist; the web UI doesn't expose that at all so a mistaken
// order can't be deleted by accident from here. update() carries the
// same "no payments yet" guard so line items/totals never drift out
// of sync with money already recorded against them.
class OrderPageController extends Controller
{
    public function index(Company $company)
    {
        $orders = Order::where('company_id', $company->id)
            ->with(['contact', 'items.product', 'payments'])
            ->latest()
            ->get();

        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit_price']);

        // For stock-tracked companies, tell the form how much of each
        // product is actually available so staff aren't guessing and
        // hitting the server-side rejection blind. This is a UX hint
        // only — allocateStock() below is what actually enforces it.
        if ($company->tracksStock()) {
            $stockByProduct = $this->availableStockByProduct($company);

            $products = $products->map(function ($product) use ($stockByProduct) {
                $product->available_qty = (int) ($stockByProduct[$product->id] ?? 0);

                return $product;
            });
        }

        return view('company.orders.index', [
            'company' => $company,
            'orders' => $orders,
            'contacts' => Contact::where('company_id', $company->id)
                ->where('type', 'customer')
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => $products,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreOrderPageRequest $request, Company $company)
    {
        $this->assertBelongsToCompany($request, $company);

        $order = DB::transaction(function () use ($request, $company) {
            // Inside the transaction on purpose: if a line below is
            // refused for lack of stock, a contact created just for
            // this order is rolled back with it instead of being left
            // behind with no order.
            $contactId = $this->resolveContactId($request, $company);

            $items = $request->validated('items');
            $total = collect($items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);

            $order = Order::create([
                'company_id' => $company->id,
                'contact_id' => $contactId,
                'order_number' => $this->generateOrderNumber($company),
                'total_amount' => $total,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $orderItem = $order->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                // Throws and rolls back the whole transaction —
                // including the order and every item already created
                // in this loop — the moment one line can't be
                // fulfilled from stock. A stock-tracked order either
                // goes through in full or not at all; it never gets
                // created partially-stocked.
                $this->allocateStock($orderItem, $company);
            }

            return $order;
        });

        return redirect()->route('company.orders.index', ['company' => $company->slug])
            ->with('status', "Order {$order->order_number} added.");
    }

    public function update(StoreOrderPageRequest $request, Company $company, Order $order)
    {
        abort_unless($order->company_id === $company->id, 404);
        abort_if($order->payments()->exists(), 422, 'Cannot edit an order that already has payments logged.');
        $this->assertBelongsToCompany($request, $company);

        DB::transaction(function () use ($request, $company, $order) {
            // Give back whatever the order's current items drew from
            // stock BEFORE checking the new items against it — otherwise
            // editing an order's quantity from 5 to 6 of the same
            // product would look like "need 6 more" instead of "need
            // 1 more", and fail even when nothing actually changed.
            $order->load('items');
            $this->restoreStock($order, $company);

            $contactId = $this->resolveContactId($request, $company);

            $items = $request->validated('items');
            $total = collect($items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);

            $order->items()->delete();

            foreach ($items as $item) {
                $orderItem = $order->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                $this->allocateStock($orderItem, $company);
            }

            $order->update([
                'contact_id' => $contactId,
                'total_amount' => $total,
            ]);
        });

        return redirect()->route('company.orders.index', ['company' => $company->slug])
            ->with('status', "Order {$order->order_number} updated.");
    }

    // Units currently sellable per product, keyed by product id.
    // Pharmacy counts only active, unexpired batches; stock-tracked
    // types count active lots. Mirrors exactly what allocateStock()
    // is allowed to draw from, so the hint can't disagree with the
    // real check.
    private function availableStockByProduct(Company $company)
    {
        if ($company->type === 'pharmacy') {
            return PharmacyBatch::where('company_id', $company->id)
                ->where('status', 'active')
                ->where('expiry_date', '>=', now()->toDateString())
                ->selectRaw('product_id, SUM(quantity_remaining) as total')
                ->groupBy('product_id')
                ->pluck('total', 'product_id');
        }

        return StockLot::where('company_id', $company->id)
            ->where('status', 'active')
            ->selectRaw('product_id, SUM(quantity_remaining) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');
    }

    // Draws the item's quantity down from stock for companies that
    // track it, and does nothing for custom lines (no product) or for
    // company types with no stock table. Throws if there's no stock
    // for the product at all, or not enough of it — this is the
    // actual enforcement, not just a UI warning.
    private function allocateStock(OrderItem $item, Company $company): void
    {
        if (! $item->product_id) {
            return;
        }

        if ($company->type === 'pharmacy') {
            $this->allocatePharmacyStock($item, $company);
        } elseif (in_array($company->type, Company::STOCK_TRACKED_TYPES, true)) {
            $this->allocateLotStock($item, $company);
        }
    }

    // Pharmacy: active, unexpired batches that expire soonest first
    // (FEFO), splitting across batches if one alone isn't enough.
    private function allocatePharmacyStock(OrderItem $item, Company $company): void
    {
        $remaining = $item->quantity;

        $batches = PharmacyBatch::where('company_id', $company->id)
            ->where('product_id', $item->product_id)
            ->where('status', 'active')
            ->where('expiry_date', '>=', now()->toDateString())
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiry_date') // FEFO — soonest-expiring stock drawn first
            ->lockForUpdate()
            ->get();

        if ($batches->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => "No stock found for \"{$item->description}\" — add a batch under Operations > Pharmacy stock before this order can go through.",
            ]);
        }

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($batch->quantity_remaining, $remaining);

            $batch->quantity_remaining -= $take;
            if ($batch->quantity_remaining === 0) {
                $batch->status = 'depleted';
            }
            $batch->save();

            OrderItemBatch::create([
                'order_item_id' => $item->id,
                'pharmacy_batch_id' => $batch->id,
                'quantity' => $take,
            ]);

            $remaining -= $take;
        }

        if ($remaining > 0) {
            $available = $item->quantity - $remaining;
            throw ValidationException::withMessages([
                'items' => "Not enough stock for \"{$item->description}\" — only {$available} of {$item->quantity} available across active batches.",
            ]);
        }
    }

    // Stock-tracked types (hair vendor): active lots, oldest received
    // first (FIFO — no expiry to order by), splitting across lots if
    // one alone isn't enough.
    private function allocateLotStock(OrderItem $item, Company $company): void
    {
        $remaining = $item->quantity;

        $lots = StockLot::where('company_id', $company->id)
            ->where('product_id', $item->product_id)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_at')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        if ($lots->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => "No stock found for \"{$item->description}\" — add it under Operations > Stock before this order can go through.",
            ]);
        }

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($lot->quantity_remaining, $remaining);

            $lot->quantity_remaining -= $take;
            if ($lot->quantity_remaining === 0) {
                $lot->status = 'depleted';
            }
            $lot->save();

            OrderItemStockLot::create([
                'order_item_id' => $item->id,
                'stock_lot_id' => $lot->id,
                'quantity' => $take,
            ]);

            $remaining -= $take;
        }

        if ($remaining > 0) {
            $available = $item->quantity - $remaining;
            throw ValidationException::withMessages([
                'items' => "Not enough stock for \"{$item->description}\" — only {$available} of {$item->quantity} in stock.",
            ]);
        }
    }

    // Reverses allocateStock() for every item currently on the order.
    // Called before an order's items are replaced during update(),
    // and safe to no-op for company types that don't track stock.
    private function restoreStock(Order $order, Company $company): void
    {
        if ($company->type === 'pharmacy') {
            $this->restorePharmacyStock($order);
        } elseif (in_array($company->type, Company::STOCK_TRACKED_TYPES, true)) {
            $this->restoreLotStock($order);
        }
    }

    private function restorePharmacyStock(Order $order): void
    {
        $allocations = OrderItemBatch::whereIn('order_item_id', $order->items->pluck('id'))
            ->lockForUpdate()
            ->get();

        foreach ($allocations as $allocation) {
            $batch = PharmacyBatch::lockForUpdate()->find($allocation->pharmacy_batch_id);

            if ($batch) {
                $batch->quantity_remaining += $allocation->quantity;
                if ($batch->status === 'depleted') {
                    $batch->status = $batch->isExpired() ? 'expired' : 'active';
                }
                $batch->save();
            }

            $allocation->delete();
        }
    }

    private function restoreLotStock(Order $order): void
    {
        $allocations = OrderItemStockLot::whereIn('order_item_id', $order->items->pluck('id'))
            ->lockForUpdate()
            ->get();

        foreach ($allocations as $allocation) {
            $lot = StockLot::lockForUpdate()->find($allocation->stock_lot_id);

            if ($lot) {
                $lot->quantity_remaining += $allocation->quantity;
                if ($lot->status === 'depleted') {
                    $lot->status = 'active';
                }
                $lot->save();
            }

            $allocation->delete();
        }
    }

    // Same gap already closed for Deals/PharmacyBatches: StoreOrderRequest's
    // bare 'exists:' rules only confirm the row exists SOMEWHERE, not
    // that it belongs to THIS company (the validator queries the raw
    // table directly, bypassing CompanyScope). A contact_id is only
    // checked when one was actually picked — a brand-new contact is
    // created for this company by resolveContactId(), so it can't
    // belong anywhere else.
    private function assertBelongsToCompany(StoreOrderPageRequest $request, Company $company): void
    {
        $contactId = $request->validated('contact_id');
        $contactOk = ! $contactId || Contact::where('id', $contactId)
            ->where('company_id', $company->id)
            ->exists();

        $productIds = collect($request->validated('items'))->pluck('product_id')->filter();
        $productsOk = $productIds->isEmpty()
            || Product::where('company_id', $company->id)->whereIn('id', $productIds)->count() === $productIds->unique()->count();

        abort_unless($contactOk && $productsOk, 422, 'Selected contact or product does not belong to this company.');
    }

    // Returns the contact this order is for: the one picked from the
    // list, or — when the person used "+ Add new contact" — a fresh
    // customer created from what they typed. New contacts are always
    // type "customer" because that's the only type the Orders page
    // lists, so they show up in the dropdown next time.
    private function resolveContactId(StoreOrderPageRequest $request, Company $company): string
    {
        if ($contactId = $request->validated('contact_id')) {
            return $contactId;
        }

        return Contact::create([
            'company_id' => $company->id,
            'type' => 'customer',
            'name' => $request->validated('new_contact_name'),
            'phone' => $request->validated('new_contact_phone'),
            'email' => $request->validated('new_contact_email'),
        ])->id;
    }

    // Delegates to DocumentNumber so the sequence is shared by every
    // company with the same 3-letter prefix (see that class).
    private function generateOrderNumber(Company $company): string
    {
        return DocumentNumber::order($company->name);
    }
}
