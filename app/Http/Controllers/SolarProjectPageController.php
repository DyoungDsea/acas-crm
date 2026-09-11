<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteSurveyRequest;
use App\Http\Requests\StoreSolarProjectRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\Contact;
use App\Models\SolarProject;

// Energy-only. The sidebar hides this from every other company type
// via config/menus.php's 'types' filter, but that's a UX convenience,
// not enforcement, so every method here also checks $company->type
// directly.
//
// Site surveys are a project's child resource (a project can have
// several over time — initial survey, follow-up after site changes)
// rather than their own menu item, same reasoning Order line items
// aren't a separate page. Both survey actions are guarded by
// confirming the parent project belongs to this company first, since
// SiteSurvey itself has no company_id column — only solar_project_id.
//
// No destroy() anywhere in this controller, same reasoning as every
// other operational record in this app: a project or a survey is a
// record of work done or scheduled, not a draft. Correcting a mistake
// means editing it (including setting status to "completed"), not
// removing it.
class SolarProjectPageController extends Controller
{
    public function index(Company $company)
    {
        abort_unless($company->type === 'energy', 404);

        $projects = SolarProject::where('company_id', $company->id)
            ->with(['contact', 'siteSurveys.surveyor'])
            ->latest('start_date')
            ->get();

        return view('company.solar-projects.index', [
            'company' => $company,
            'projects' => $projects,
            'contacts' => Contact::where('company_id', $company->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreSolarProjectRequest $request, Company $company)
    {
        abort_unless($company->type === 'energy', 404);
        $this->assertContactBelongsToCompany($request, $company);

        $project = SolarProject::create([
            ...$request->validated(),
            'company_id' => $company->id,
        ]);

        return redirect()->route('company.solar-projects.index', ['company' => $company->slug])
            ->with('status', "Project \"{$project->title}\" added.");
    }

    public function update(StoreSolarProjectRequest $request, Company $company, SolarProject $solar_project)
    {
        abort_unless($company->type === 'energy' && $solar_project->company_id === $company->id, 404);
        $this->assertContactBelongsToCompany($request, $company);

        $solar_project->update($request->validated());

        return redirect()->route('company.solar-projects.index', ['company' => $company->slug])
            ->with('status', "Project \"{$solar_project->title}\" updated.");
    }

    public function storeSurvey(StoreSiteSurveyRequest $request, Company $company, SolarProject $solar_project)
    {
        abort_unless($company->type === 'energy' && $solar_project->company_id === $company->id, 404);

        $solar_project->siteSurveys()->create([
            ...$request->validated(),
            // Same as Api\SiteSurveyController — the surveyor is
            // whoever is submitting the survey, never a hand-picked
            // dropdown value.
            'surveyed_by' => Auth::id(),
        ]);

        return redirect()->route('company.solar-projects.index', ['company' => $company->slug])
            ->with('status', "Survey added to \"{$solar_project->title}\".");
    }

    public function updateSurvey(StoreSiteSurveyRequest $request, Company $company, SolarProject $solar_project, string $survey)
    {
        abort_unless($company->type === 'energy' && $solar_project->company_id === $company->id, 404);

        $surveyModel = $solar_project->siteSurveys()->findOrFail($survey);
        $surveyModel->update($request->validated());

        return redirect()->route('company.solar-projects.index', ['company' => $company->slug])
            ->with('status', "Survey updated on \"{$solar_project->title}\".");
    }

    // Same gap already closed for Deals/Orders/Engagements: bare
    // 'exists:' rules in StoreSolarProjectRequest only confirm the
    // contact row exists SOMEWHERE, not that it belongs to THIS
    // company (the validator queries the raw table directly,
    // bypassing CompanyScope).
    private function assertContactBelongsToCompany(StoreSolarProjectRequest $request, Company $company): void
    {
        $contactOk = Contact::where('id', $request->validated('contact_id'))
            ->where('company_id', $company->id)
            ->exists();

        abort_unless($contactOk, 422, 'Selected contact does not belong to this company.');
    }
}
