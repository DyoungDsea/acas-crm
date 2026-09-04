<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDealRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Only for companies where a negotiated, multi-stage sale actually
// fits the business — see Company::PIPELINE_ENABLED_TYPES. Same
// enforcement reasoning as PipelinePageController: the sidebar
// already hides this for excluded types, but that's UX, not the
// actual boundary.
//
// No delete on this controller on purpose, same reasoning as Orders
// and PharmacyBatches — a deal that isn't going anywhere gets marked
// status: lost, not erased. The status field already exists exactly
// for this.
class DealPageController extends Controller
{
    public function index(Company $company)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);

        $deals = Deal::where('company_id', $company->id)
            ->with(['contact', 'stage.pipeline', 'owner'])
            ->latest()
            ->get();

        return view('company.deals.index', [
            'company' => $company,
            'deals' => $deals,
            'contacts' => Contact::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'stages' => PipelineStage::whereHas('pipeline', fn($q) => $q->where('company_id', $company->id))
                ->with('pipeline')
                ->orderBy('order')
                ->get(),
            'staff' => User::where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreDealRequest $request, Company $company)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);
        $this->assertBelongsToCompany($request, $company);

        $deal = Deal::create([
            ...$request->validated(),
            'company_id' => $company->id,
            'owner_id' => $request->validated('owner_id') ?: Auth::id(),
        ]);

        return redirect()->route('company.deals.index', ['company' => $company->slug])->with('status', "{$deal->title} added.");
    }

    public function update(StoreDealRequest $request, Company $company, Deal $deal)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);
        abort_unless($deal->company_id === $company->id, 404);
        $this->assertBelongsToCompany($request, $company);

        $deal->update($request->validated());

        return redirect()->route('company.deals.index', ['company' => $company->slug])->with('status', "{$deal->title} updated.");
    }

    // StoreDealRequest's 'exists:contacts,id' / 'exists:pipeline_stages,id'
    // rules only confirm the ROW exists somewhere — they don't check
    // it belongs to THIS company, since the validator queries the raw
    // table directly and never goes through Eloquent's CompanyScope.
    private function assertBelongsToCompany(StoreDealRequest $request, Company $company): void
    {
        $contactOk = Contact::where('id', $request->validated('contact_id'))
            ->where('company_id', $company->id)
            ->exists();

        $stageOk = PipelineStage::where('id', $request->validated('pipeline_stage_id'))
            ->whereHas('pipeline', fn($q) => $q->where('company_id', $company->id))
            ->exists();

        abort_unless($contactOk && $stageOk, 422, 'Selected contact or stage does not belong to this company.');
    }
}
