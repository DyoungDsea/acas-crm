<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaseRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lease;
use App\Models\PropertyUnit;

// Properties-only, same type guard as PropertyPageController. On its
// own page rather than nested under Properties because a lease is
// naturally viewed and reported on across every property at once —
// "who's renting what, and when does it end" — not one property's
// concern at a time.
//
// No destroy() on purpose, same reasoning as every other contract-like
// record in this app (Engagements, Orders): a lease is history — who
// rented what, when, at what rent — not a draft. Ending one means
// setting status to "ended"/"terminated", not removing it.
//
// Worth knowing: leases_table's own migration comment says "each rent
// cycle creates a new order against this lease", implying a future
// recurring-billing feature. That doesn't exist yet — there's no
// lease_id column on orders, and nothing here creates one. A lease's
// rent_amount is just descriptive; actually billing a tenant still
// means creating an Order by hand today.
class LeasePageController extends Controller
{
    public function index(Company $company)
    {
        abort_unless($company->type === 'properties', 404);

        $leases = Lease::where('company_id', $company->id)
            ->with(['propertyUnit.property', 'contact'])
            ->latest('start_date')
            ->get();

        return view('company.leases.index', [
            'company' => $company,
            'leases' => $leases,
            'units' => PropertyUnit::whereHas('property', fn($q) => $q->where('company_id', $company->id))
                ->with('property')
                ->get()
                ->map(fn($unit) => [
                    'id' => $unit->id,
                    'label' => ($unit->property->title ?? '—') . ' — ' . $unit->unit_label,
                    'status' => $unit->status,
                ]),
            'contacts' => Contact::where('company_id', $company->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreLeaseRequest $request, Company $company)
    {
        abort_unless($company->type === 'properties', 404);
        $unit = $this->assertBelongsToCompanyAndGetUnit($request, $company);

        // Not enforced by Api\LeaseController, but leaving it out
        // would let two active leases exist on the same unit at
        // once — closing that gap here the same way stock/settings
        // checks were added to Orders/Payments.
        $hasActiveLease = Lease::where('property_unit_id', $unit->id)->where('status', 'active')->exists();
        abort_if($hasActiveLease, 422, 'This unit already has an active lease — end it before starting a new one.');

        $lease = Lease::create([
            ...$request->validated(),
            'company_id' => $company->id,
        ]);

        // Same side effect as Api\LeaseController::store().
        $lease->propertyUnit->update(['status' => 'occupied']);

        return redirect()->route('company.leases.index', ['company' => $company->slug])
            ->with('status', "Lease for {$unit->unit_label} added.");
    }

    public function update(StoreLeaseRequest $request, Company $company, Lease $lease)
    {
        abort_unless($company->type === 'properties' && $lease->company_id === $company->id, 404);
        $this->assertBelongsToCompanyAndGetUnit($request, $company);

        $lease->update($request->validated());

        // Same side effect as Api\LeaseController::update() — only
        // reacts to a lease ending, never re-occupies on its own if
        // status is edited back to active, matching the API exactly.
        if (in_array($lease->status, ['ended', 'terminated'])) {
            $lease->propertyUnit->update(['status' => 'vacant']);
        }

        return redirect()->route('company.leases.index', ['company' => $company->slug])
            ->with('status', 'Lease updated.');
    }

    // Same gap already closed for Deals/Orders/Engagements/SolarProjects:
    // StoreLeaseRequest's bare 'exists:' rules only confirm the rows
    // exist SOMEWHERE, not that they belong to THIS company. property_unit_id
    // has no company_id of its own, so ownership is checked through its
    // parent Property.
    private function assertBelongsToCompanyAndGetUnit(StoreLeaseRequest $request, Company $company): PropertyUnit
    {
        $contactOk = Contact::where('id', $request->validated('contact_id'))
            ->where('company_id', $company->id)
            ->exists();

        abort_unless($contactOk, 422, 'Selected contact does not belong to this company.');

        $unit = PropertyUnit::whereHas('property', fn($q) => $q->where('company_id', $company->id))
            ->find($request->validated('property_unit_id'));

        abort_unless($unit, 422, 'Selected unit does not belong to this company.');

        return $unit;
    }
}
