<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEngagementRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Engagement;
use App\Models\User;

// Available to two company types (agric_services and farms — see the
// migration's own comment on why one table covers both), unlike
// PharmacyBatch/CropLivestock which are single-type. The sidebar
// already filters via config/menus.php's 'types' array, but that's a
// UX convenience, not enforcement — every method here also checks
// $company->type directly.
//
// No destroy() on purpose, same reasoning as every other operational
// record in this app (Deals, PharmacyBatches, CropCycles): an
// engagement is a record of advisory work performed, not a draft.
// Correcting a mistake means editing it (including setting status to
// "cancelled"), not removing it.
class EngagementPageController extends Controller
{
    private const ALLOWED_TYPES = ['agric_services', 'farms'];

    public function index(Company $company)
    {
        abort_unless(in_array($company->type, self::ALLOWED_TYPES), 404);

        $engagements = Engagement::where('company_id', $company->id)
            ->with(['contact', 'leadAdvisor'])
            ->latest('start_date')
            ->get();

        return view('company.engagements.index', [
            'company' => $company,
            'engagements' => $engagements,
            'contacts' => Contact::where('company_id', $company->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'advisors' => User::where('company_id', $company->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreEngagementRequest $request, Company $company)
    {
        abort_unless(in_array($company->type, self::ALLOWED_TYPES), 404);
        $this->assertBelongsToCompany($request, $company);

        $engagement = Engagement::create([
            ...$request->validated(),
            'company_id' => $company->id,
        ]);

        return redirect()->route('company.engagements.index', ['company' => $company->slug])
            ->with('status', "Engagement \"{$engagement->title}\" added.");
    }

    public function update(StoreEngagementRequest $request, Company $company, Engagement $engagement)
    {
        abort_unless(in_array($company->type, self::ALLOWED_TYPES) && $engagement->company_id === $company->id, 404);
        $this->assertBelongsToCompany($request, $company);

        $engagement->update($request->validated());

        return redirect()->route('company.engagements.index', ['company' => $company->slug])
            ->with('status', "Engagement \"{$engagement->title}\" updated.");
    }

    // Same gap already closed for Deals/PharmacyBatches/Orders:
    // StoreEngagementRequest's bare 'exists:' rules only confirm the
    // row exists SOMEWHERE, not that it belongs to THIS company (the
    // validator queries the raw table directly, bypassing
    // CompanyScope).
    private function assertBelongsToCompany(StoreEngagementRequest $request, Company $company): void
    {
        $contactOk = Contact::where('id', $request->validated('contact_id'))
            ->where('company_id', $company->id)
            ->exists();

        $advisorId = $request->validated('lead_advisor');
        $advisorOk = ! $advisorId || User::where('id', $advisorId)
            ->where('company_id', $company->id)
            ->exists();

        abort_unless($contactOk && $advisorOk, 422, 'Selected contact or advisor does not belong to this company.');
    }
}
