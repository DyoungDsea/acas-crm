<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleListingRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;

// Automotive-only. Replaces the old Vehicle model entirely (per the
// person's explicit decision) — every vehicle is now a Product row,
// so it can be picked directly as an Order line item, same as any
// other company's stock, instead of living in a parallel table with
// no billing concept.
//
// Two categories ("Vehicles — For Sale" / "Vehicles — For Rent") are
// auto-provisioned per company on first visit via firstOrCreate,
// rather than requiring the admin to set them up by hand or matching
// on an arbitrary admin-chosen category. Known trade-off: this keys
// on the category NAME, so if an admin renames one of these two
// categories from the generic Categories page, a fresh one gets
// silently recreated next time this page loads — existing listings
// stay pointed at the renamed category (nothing breaks), but a
// second "Vehicles — For Sale" would appear. Worth a firmer link
// (e.g. a dedicated column) if that turns out to bite in practice.
//
// vehicle_status, make/model/registration/year/condition all live in
// Product's attributes JSON — there's no separate vehicles table
// anymore. No destroy(): same as generic Products, "removing" a
// listing means marking it unavailable, not deleting the row (a sold
// vehicle's row is the only record that it was ever sold).
class VehicleListingPageController extends Controller
{
    public function index(Company $company)
    {
        abort_unless($company->type === 'automotive', 404);

        [$saleCategory, $rentCategory] = $this->vehicleCategories($company);

        $listings = Product::where('company_id', $company->id)
            ->whereIn('category_id', [$saleCategory->id, $rentCategory->id])
            ->orderBy('name')
            ->get();

        return view('company.vehicle-listings.index', [
            'company' => $company,
            'listings' => $listings,
            'saleCategoryId' => $saleCategory->id,
            'rentCategoryId' => $rentCategory->id,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreVehicleListingRequest $request, Company $company)
    {
        abort_unless($company->type === 'automotive', 404);

        [$saleCategory, $rentCategory] = $this->vehicleCategories($company);
        $data = $request->validated();

        $product = Product::create([
            'company_id' => $company->id,
            'category_id' => $data['listing_type'] === 'sale' ? $saleCategory->id : $rentCategory->id,
            'name' => "{$data['make']} {$data['model']}" . (($data['registration_number'] ?? null) ? " ({$data['registration_number']})" : ''),
            'unit_price' => $data['price'],
            'attributes' => [
                'make' => $data['make'],
                'model' => $data['model'],
                'registration_number' => $data['registration_number'] ?? null,
                'year' => $data['year'] ?? null,
                'condition' => $data['condition'] ?? null,
                'vehicle_status' => $data['vehicle_status'],
            ],
            'is_active' => $data['vehicle_status'] !== 'sold',
        ]);

        return redirect()->route('company.vehicle-listings.index', ['company' => $company->slug])
            ->with('status', "{$product->name} added.");
    }

    public function update(StoreVehicleListingRequest $request, Company $company, Product $vehicle_listing)
    {
        abort_unless($company->type === 'automotive' && $vehicle_listing->company_id === $company->id, 404);

        [$saleCategory, $rentCategory] = $this->vehicleCategories($company);
        $data = $request->validated();

        $vehicle_listing->update([
            'category_id' => $data['listing_type'] === 'sale' ? $saleCategory->id : $rentCategory->id,
            'name' => "{$data['make']} {$data['model']}" . (($data['registration_number'] ?? null) ? " ({$data['registration_number']})" : ''),
            'unit_price' => $data['price'],
            'attributes' => [
                'make' => $data['make'],
                'model' => $data['model'],
                'registration_number' => $data['registration_number'] ?? null,
                'year' => $data['year'] ?? null,
                'condition' => $data['condition'] ?? null,
                'vehicle_status' => $data['vehicle_status'],
            ],
            // Same as a plain sale from the transactions page — sold
            // means gone from the active catalog. Any other status
            // (including switching back off "sold" by correcting a
            // mistake) reactivates it.
            'is_active' => $data['vehicle_status'] !== 'sold',
        ]);

        return redirect()->route('company.vehicle-listings.index', ['company' => $company->slug])
            ->with('status', "{$vehicle_listing->name} updated.");
    }

    private function vehicleCategories(Company $company): array
    {
        $sale = ProductCategory::firstOrCreate(['company_id' => $company->id, 'name' => 'Vehicles — For Sale']);
        $rent = ProductCategory::firstOrCreate(['company_id' => $company->id, 'name' => 'Vehicles — For Rent']);

        return [$sale, $rent];
    }
}
