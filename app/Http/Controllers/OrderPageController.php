<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBatch;
use App\Models\PharmacyBatch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Available to every company type (unlike PharmacyBatch/SiteSurvey
// etc.), so no abort_unless($company->type === ...) guard here.
// Stock drawdown (allocateStock/restoreStock below) is the one part
// of this controller that IS pharmacy-only — every other company
// type has no stock table to check against yet, so those methods
// no-op immediately for anything else.
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

        // For pharmacy companies, tell the form how much of each
        // product is actually available (active, unexpired batches)
        // so staff aren't guessing and hitting the server-side
        // rejection blind. This is a UX hint only — allocateStock()
        // below is what actually enforces it.
        if ($company->type === 'pharmacy') {
            $stockByProduct = PharmacyBatch::where('company_id', $company->id)
                ->where('status', 'active')
                ->where('expiry_date', '>=', now()->toDateString())
                ->selectRaw('product_id, SUM(quantity_remaining) as total')
                ->groupBy('product_id')
                ->pluck('total', 'product_id');

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

    public function store(StoreOrderRequest $request, Company $company)
    {
        $this->assertBelongsToCompany($request, $company);

        $order = DB::transaction(function () use ($request, $company) {
            $items = $request->validated('items');
            $total = collect($items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);

            $order = Order::create([
                'company_id' => $company->id,
                'contact_id' => $request->validated('contact_id'),
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
                // fulfilled from stock. A pharmacy order either goes
                // through in full or not at all; it never gets
                // created partially-stocked.
                $this->allocateStock($orderItem, $company);
            }

            return $order;
        });

        return redirect()->route('company.orders.index', ['company' => $company->slug])
            ->with('status', "Order {$order->order_number} added.");
    }

    public function update(StoreOrderRequest $request, Company $company, Order $order)
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
            $order->load('items.batchAllocations');
            $this->restoreStock($order, $company);

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
                'contact_id' => $request->validated('contact_id'),
                'total_amount' => $total,
            ]);
        });

        return redirect()->route('company.orders.index', ['company' => $company->slug])
            ->with('status', "Order {$order->order_number} updated.");
    }

    // Pharmacy-only. Draws the item's quantity down from whichever
    // active, unexpired batch(es) of that product expire soonest
    // (FEFO), splitting across batches if one alone isn't enough.
    // Throws if there's no batch for the product at all, or not
    // enough combined stock across all of them — this is the actual
    // enforcement the person asked for, not just a UI warning.
    private function allocateStock(OrderItem $item, Company $company): void
    {
        if ($company->type !== 'pharmacy' || ! $item->product_id) {
            return;
        }

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

    // Reverses allocateStock() for every item currently on the order:
    // gives each drawn batch its quantity back, un-depletes it if it
    // had hit zero, and clears the ledger rows. Called before an
    // order's items are replaced during update(), and safe to no-op
    // for non-pharmacy companies or items with no allocations.
    private function restoreStock(Order $order, Company $company): void
    {
        if ($company->type !== 'pharmacy') {
            return;
        }

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

    // Same gap already closed for Deals/PharmacyBatches: StoreOrderRequest's
    // bare 'exists:' rules only confirm the row exists SOMEWHERE, not
    // that it belongs to THIS company (the validator queries the raw
    // table directly, bypassing CompanyScope).
    private function assertBelongsToCompany(StoreOrderRequest $request, Company $company): void
    {
        $contactOk = Contact::where('id', $request->validated('contact_id'))
            ->where('company_id', $company->id)
            ->exists();

        $productIds = collect($request->validated('items'))->pluck('product_id')->filter();
        $productsOk = $productIds->isEmpty()
            || Product::where('company_id', $company->id)->whereIn('id', $productIds)->count() === $productIds->unique()->count();

        abort_unless($contactOk && $productsOk, 422, 'Selected contact or product does not belong to this company.');
    }

    // Scoped per-company on purpose — Api\OrderController's version of
    // this counts across ALL companies, so two companies both hit
    // "-000001" style prefixes but share one drifting global sequence.
    // Scoping the count fixes that without changing the number format.
    private function generateOrderNumber(Company $company): string
    {
        $prefix = strtoupper(Str::substr(Str::slug($company->name), 0, 3));
        $year = now()->year;
        $sequence = str_pad(
            (string) (Order::where('company_id', $company->id)->whereYear('created_at', $year)->count() + 1),
            6,
            '0',
            STR_PAD_LEFT
        );

        return "{$prefix}-{$year}-{$sequence}";
    }
}
