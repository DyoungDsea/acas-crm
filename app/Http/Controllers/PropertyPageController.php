<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\StorePropertyUnitRequest;
use App\Models\Company;
use App\Models\Property;

// Properties-only. The sidebar hides this from every other company
// type via config/menus.php's 'types' filter, but that's a UX
// convenience, not enforcement, so every method here also checks
// $company->type directly.
//
// Units are a property's child resource (a property can have several
// — one row per flat/shop/plot) rather than their own menu item,
// same reasoning SiteSurveys aren't a separate page from Solar
// Projects. Both unit actions are guarded by confirming the parent
// property belongs to this company first, since PropertyUnit itself
// has no company_id column — only property_id.
//
// Leases live on their own page (LeasePageController), not here —
// a lease is queried and reported on across every property at once,
// which doesn't fit "nested inside one property's modal" the way a
// handful of units per property does.
//
// No destroy() on properties or units: a property/unit is a physical
// asset record, not a draft. Selling a property or retiring a unit
// means changing its status, not deleting the row — deleting a
// property would also cascade-delete every unit and every lease
// against those units, which is exactly the kind of silent data loss
// this app avoids everywhere else.
class PropertyPageController extends Controller
{
    public function index(Company $company)
    {
        abort_unless($company->type === 'properties', 404);

        $properties = Property::where('company_id', $company->id)
            ->with('units')
            ->orderBy('title')
            ->get();

        return view('company.properties.index', [
            'company' => $company,
            'properties' => $properties,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StorePropertyRequest $request, Company $company)
    {
        abort_unless($company->type === 'properties', 404);

        $property = Property::create([
            ...$request->validated(),
            'company_id' => $company->id,
        ]);

        return redirect()->route('company.properties.index', ['company' => $company->slug])
            ->with('status', "Property \"{$property->title}\" added.");
    }

    public function update(StorePropertyRequest $request, Company $company, Property $property)
    {
        abort_unless($company->type === 'properties' && $property->company_id === $company->id, 404);

        $property->update($request->validated());

        return redirect()->route('company.properties.index', ['company' => $company->slug])
            ->with('status', "Property \"{$property->title}\" updated.");
    }

    public function storeUnit(StorePropertyUnitRequest $request, Company $company, Property $property)
    {
        abort_unless($company->type === 'properties' && $property->company_id === $company->id, 404);

        $unit = $property->units()->create($request->validated());

        return redirect()->route('company.properties.index', ['company' => $company->slug])
            ->with('status', "Unit \"{$unit->unit_label}\" added to \"{$property->title}\".");
    }

    public function updateUnit(StorePropertyUnitRequest $request, Company $company, Property $property, string $unit)
    {
        abort_unless($company->type === 'properties' && $property->company_id === $company->id, 404);

        $unitModel = $property->units()->findOrFail($unit);
        $unitModel->update($request->validated());

        return redirect()->route('company.properties.index', ['company' => $company->slug])
            ->with('status', "Unit \"{$unitModel->unit_label}\" updated.");
    }
}
