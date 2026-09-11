<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleTransactionRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Automotive-only. This IS the Orders page for this company type —
// the generic "All orders" menu item is hidden for automotive (see
// config/menus.php's 'excludeTypes') specifically so there's one
// obvious place to create a vehicle sale/rental instead of two
// overlapping ones. Taking payment and viewing/printing the receipt
// still happen on the universal Payments/Receipts pages — those work
// on any Order regardless of what created it, so there's nothing
// vehicle-specific to build there.
//
// Structurally this mirrors OrderPageController almost exactly
// (create/edit only while unpaid, total/paid/balance from the Order
// model, no destroy), just with exactly one line item instead of a
// repeater, since a sale/rental is always exactly one vehicle.
class VehicleTransactionPageController extends Controller
{
    public function index(Company $company)
    {
        abort_unless($company->type === 'automotive', 404);

        $vehicleCategoryIds = $this->vehicleCategoryIds($company);

        $transactions = Order::where('company_id', $company->id)
            ->whereHas('items.product', fn($q) => $q->whereIn('category_id', $vehicleCategoryIds))
            ->with(['contact', 'items.product', 'payments'])
            ->latest()
            ->get();

        return view('company.vehicle-transactions.index', [
            'company' => $company,
            'transactions' => $transactions,
            'vehicleOptions' => $this->vehicleOptions($company, $vehicleCategoryIds),
            'contacts' => Contact::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreVehicleTransactionRequest $request, Company $company)
    {
        abort_unless($company->type === 'automotive', 404);

        $product = Product::where('company_id', $company->id)->findOrFail($request->validated('vehicle_product_id'));
        $isRental = optional($product->category)->name === 'Vehicles — For Rent';

        abort_unless(($product->attributes['vehicle_status'] ?? null) === 'available', 422, 'This vehicle is no longer available.');
        $this->assertContactBelongsToCompany($request, $company);

        if ($isRental && ! $request->validated('rental_start_at')) {
            throw ValidationException::withMessages(['rental_start_at' => 'A rental needs a start date.']);
        }

        $order = DB::transaction(function () use ($request, $company, $product, $isRental) {
            $order = Order::create([
                'company_id' => $company->id,
                'contact_id' => $request->validated('contact_id'),
                'order_number' => $this->generateOrderNumber($company),
                'total_amount' => $product->unit_price,
                'created_by' => Auth::id(),
                'rental_start_at' => $isRental ? $request->validated('rental_start_at') : null,
                'rental_end_at' => $isRental ? $request->validated('rental_end_at') : null,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => 1,
                'unit_price' => $product->unit_price,
                'subtotal' => $product->unit_price,
            ]);

            // Reserved the moment the transaction is created, same as
            // OrderPageController draws down pharmacy stock immediately
            // on order creation — availability is a separate concern
            // from whether it's been paid for yet.
            $product->update([
                'attributes' => [...$product->attributes, 'vehicle_status' => $isRental ? 'rented' : 'sold'],
                'is_active' => $isRental,
            ]);

            return $order;
        });

        return redirect()->route('company.vehicle-transactions.index', ['company' => $company->slug])
            ->with('status', ($isRental ? 'Rental' : 'Sale') . " recorded for {$product->name}. Record payment on the Payments page.");
    }

    public function update(StoreVehicleTransactionRequest $request, Company $company, Order $order)
    {
        abort_unless($company->type === 'automotive' && $order->company_id === $company->id, 404);
        abort_if($order->payments()->exists(), 422, 'Cannot edit a transaction that already has payments logged.');
        $this->assertContactBelongsToCompany($request, $company);

        $currentItem = $order->items->first();
        $newProduct = Product::where('company_id', $company->id)->findOrFail($request->validated('vehicle_product_id'));
        $isRental = optional($newProduct->category)->name === 'Vehicles — For Rent';

        if ($isRental && ! $request->validated('rental_start_at')) {
            throw ValidationException::withMessages(['rental_start_at' => 'A rental needs a start date.']);
        }

        // Only enforce "must be available" if the vehicle is actually
        // changing — the currently-assigned one is legitimately
        // "unavailable" precisely because this order reserved it.
        $vehicleChanged = $currentItem?->product_id !== $newProduct->id;
        if ($vehicleChanged) {
            abort_unless(($newProduct->attributes['vehicle_status'] ?? null) === 'available', 422, 'This vehicle is no longer available.');
        }

        DB::transaction(function () use ($request, $order, $currentItem, $newProduct, $isRental, $vehicleChanged) {
            if ($vehicleChanged) {
                // Release the old vehicle back to the pool before
                // reserving the new one — mirrors restoreStock() then
                // allocateStock() in OrderPageController.
                $oldProduct = $currentItem?->product;
                if ($oldProduct) {
                    $oldProduct->update([
                        'attributes' => [...$oldProduct->attributes, 'vehicle_status' => 'available'],
                        'is_active' => true,
                    ]);
                }

                $newProduct->update([
                    'attributes' => [...$newProduct->attributes, 'vehicle_status' => $isRental ? 'rented' : 'sold'],
                    'is_active' => $isRental,
                ]);

                $currentItem?->update([
                    'product_id' => $newProduct->id,
                    'description' => $newProduct->name,
                    'unit_price' => $newProduct->unit_price,
                    'subtotal' => $newProduct->unit_price,
                ]);
            }

            $order->update([
                'contact_id' => $request->validated('contact_id'),
                'total_amount' => $newProduct->unit_price,
                'rental_start_at' => $isRental ? $request->validated('rental_start_at') : null,
                'rental_end_at' => $isRental ? $request->validated('rental_end_at') : null,
            ]);
        });

        return redirect()->route('company.vehicle-transactions.index', ['company' => $company->slug])
            ->with('status', 'Transaction updated.');
    }

    // The only action available once a transaction has payments on
    // it (and therefore can't be edited) — it changes the vehicle's
    // availability, never the money already on record. Only
    // meaningful for rentals; a sale has nothing to "return".
    public function markReturned(Company $company, Order $order)
    {
        abort_unless($order->company_id === $company->id, 404);

        $product = optional($order->items->first())->product;
        abort_unless($product && optional($product->category)->name === 'Vehicles — For Rent', 422, 'This order is not a rental.');

        $product->update(['attributes' => [...$product->attributes, 'vehicle_status' => 'available']]);

        return redirect()->route('company.vehicle-transactions.index', ['company' => $company->slug])
            ->with('status', "{$product->name} marked available again.");
    }

    private function vehicleCategoryIds(Company $company)
    {
        return ProductCategory::where('company_id', $company->id)
            ->whereIn('name', ['Vehicles — For Sale', 'Vehicles — For Rent'])
            ->pluck('id');
    }

    // Returns EVERY vehicle listing, not just available ones — the
    // dropdown needs to render a currently-reserved vehicle too (so
    // editing a transaction can still show its own vehicle selected),
    // it's just marked unavailable via 'status' so the view can
    // disable those options except when editing that same record.
    // Same pattern as LeasePageController's unit dropdown.
    private function vehicleOptions(Company $company, $vehicleCategoryIds)
    {
        return Product::where('company_id', $company->id)
            ->whereIn('category_id', $vehicleCategoryIds)
            ->with('category')
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'label' => $product->name,
                'price' => $product->unit_price,
                'is_rental' => optional($product->category)->name === 'Vehicles — For Rent',
                'status' => $product->attributes['vehicle_status'] ?? 'available',
            ])
            ->values();
    }

    private function assertContactBelongsToCompany(StoreVehicleTransactionRequest $request, Company $company): void
    {
        $contactOk = Contact::where('id', $request->validated('contact_id'))->where('company_id', $company->id)->exists();

        abort_unless($contactOk, 422, 'Selected contact does not belong to this company.');
    }

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
