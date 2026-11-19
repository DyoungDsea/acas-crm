<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockLotRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\StockLot;
use Illuminate\Validation\ValidationException;

// Only for company types in Company::STOCK_TRACKED_TYPES (currently
// the hair vendor). Pharmacy is not one of them — it has its own
// expiry-aware PharmacyBatch page. The sidebar hides this from every
// other type via config/menus.php, but that's a UX convenience, not
// enforcement, so every method here also checks the type directly.
//
// No destroy() on purpose, same as PharmacyBatchPageController: a
// lot is stock history — what came in, when, from whom — and
// deleting one would erase the record of stock that orders already
// drew from. Correcting a mistake means editing it, not removing it.
class StockLotPageController extends Controller
{
    public function index(Company $company)
    {
        $this->guardType($company);

        $lots = StockLot::where('company_id', $company->id)
            ->with(['product', 'vendorContact'])
            ->orderByDesc('received_at')
            ->orderByDesc('created_at')
            ->get();

        // What's actually sellable right now, per product — the
        // number the person cares about most, so it gets its own
        // summary rather than making them add up lots by eye.
        $totals = StockLot::where('company_id', $company->id)
            ->where('status', 'active')
            ->selectRaw('product_id, SUM(quantity_remaining) as total')
            ->groupBy('product_id')
            ->with('product')
            ->get()
            ->sortBy(fn($row) => $row->product->name ?? '')
            ->values();

        return view('company.stock-lots.index', [
            'company' => $company,
            'lots' => $lots,
            'totals' => $totals,
            'products' => Product::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'vendors' => Contact::where('company_id', $company->id)->where('type', 'vendor')->orderBy('name')->get(['id', 'name']),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreStockLotRequest $request, Company $company)
    {
        $this->guardType($company);
        $this->assertBelongsToCompany($request, $company);

        $lot = StockLot::create([
            ...$request->validated(),
            'company_id' => $company->id,
            // A lot always starts fully in stock and active —
            // quantity_remaining only ever moves via order drawdown
            // (or a correction to quantity_received), never entered
            // by hand.
            'quantity_remaining' => $request->validated('quantity_received'),
            'status' => 'active',
        ]);

        return redirect()->route('company.stock-lots.index', ['company' => $company->slug])
            ->with('status', "{$lot->quantity_received} × {$lot->product->name} added to stock.");
    }

    public function update(StoreStockLotRequest $request, Company $company, StockLot $stock_lot)
    {
        $this->guardType($company);
        abort_unless($stock_lot->company_id === $company->id, 404);
        $this->assertBelongsToCompany($request, $company);

        $data = $request->validated();

        // How many have already been sold out of this lot. This is
        // always received - remaining, because remaining only moves
        // through order drawdown or through this very method.
        $sold = $stock_lot->quantity_received - $stock_lot->quantity_remaining;
        $newReceived = (int) $data['quantity_received'];

        if ($newReceived < $sold) {
            throw ValidationException::withMessages([
                'quantity_received' => "{$sold} from this lot have already been sold — quantity received can't be lower than that.",
            ]);
        }

        // Re-pointing a lot at a different product after some of it
        // has been sold would make those past sales claim stock of a
        // product they never drew from.
        if ($sold > 0 && $data['product_id'] !== $stock_lot->product_id) {
            throw ValidationException::withMessages([
                'product_id' => "Some of this lot has already been sold, so its product can't be changed.",
            ]);
        }

        // Correcting the received count moves what's left by the
        // same amount, so "received 50, actually 45" fixes the
        // remaining figure too instead of leaving it stale.
        $remaining = $newReceived - $sold;

        $status = $data['status'];
        if ($remaining === 0) {
            $status = 'depleted';
        } elseif ($stock_lot->quantity_remaining === 0 && $status === 'depleted') {
            // Topping up a lot that had run out — it's sellable
            // again, so reactivate it rather than leaving stock
            // invisible behind a stale "depleted" status.
            $status = 'active';
        }

        $stock_lot->update([
            'product_id' => $data['product_id'],
            'vendor_contact_id' => $data['vendor_contact_id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'received_at' => $data['received_at'],
            'quantity_received' => $newReceived,
            'quantity_remaining' => $remaining,
            'status' => $status,
        ]);

        return redirect()->route('company.stock-lots.index', ['company' => $company->slug])
            ->with('status', "Stock lot for {$stock_lot->product->name} updated.");
    }

    private function guardType(Company $company): void
    {
        abort_unless(in_array($company->type, Company::STOCK_TRACKED_TYPES, true), 404);
    }

    // product_id and vendor_contact_id use bare 'exists:' rules in
    // StoreStockLotRequest, which only confirm the row exists
    // SOMEWHERE — not that it belongs to this company. Same gap
    // closed for Deals/Orders/PharmacyBatches.
    private function assertBelongsToCompany(StoreStockLotRequest $request, Company $company): void
    {
        $productOk = Product::where('id', $request->validated('product_id'))
            ->where('company_id', $company->id)
            ->exists();

        $vendorId = $request->validated('vendor_contact_id');
        $vendorOk = ! $vendorId || Contact::where('id', $vendorId)
            ->where('company_id', $company->id)
            ->exists();

        abort_unless($productOk && $vendorOk, 422, 'Selected product or supplier does not belong to this company.');
    }
}
