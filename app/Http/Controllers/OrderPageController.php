<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// Available to every company type (unlike PharmacyBatch/SiteSurvey
// etc.), so no abort_unless($company->type === ...) guard here.
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

        return view('company.orders.index', [
            'company' => $company,
            'orders' => $orders,
            'contacts' => Contact::where('company_id', $company->id)
                ->where('type', 'customer')
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => Product::where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'unit_price']),
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
                $order->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
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

        DB::transaction(function () use ($request, $order) {
            $items = $request->validated('items');
            $total = collect($items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);

            $order->items()->delete();

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            $order->update([
                'contact_id' => $request->validated('contact_id'),
                'total_amount' => $total,
            ]);
        });

        return redirect()->route('company.orders.index', ['company' => $company->slug])
            ->with('status', "Order {$order->order_number} updated.");
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
