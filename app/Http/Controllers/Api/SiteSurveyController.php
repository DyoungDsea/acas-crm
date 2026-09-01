<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSiteSurveyRequest;
use App\Http\Resources\SiteSurveyResource;
use App\Models\SolarProject;

// Nested under /solar-projects/{solarProject}/surveys
class SiteSurveyController extends Controller
{
    public function index(SolarProject $solarProject)
    {
        return SiteSurveyResource::collection($solarProject->siteSurveys()->with('surveyor')->get());
    }

    public function store(StoreSiteSurveyRequest $request, SolarProject $solarProject)
    {
        $survey = $solarProject->siteSurveys()->create([
            ...$request->validated(),
            'surveyed_by' => $request->user()->id,
        ]);

        return new SiteSurveyResource($survey->load('surveyor'));
    }

    public function update(StoreSiteSurveyRequest $request, SolarProject $solarProject, string $survey)
    {
        $surveyModel = $solarProject->siteSurveys()->findOrFail($survey);
        $surveyModel->update($request->validated());

        return new SiteSurveyResource($surveyModel->load('surveyor'));
    }

    public function destroy(SolarProject $solarProject, string $survey)
    {
        $solarProject->siteSurveys()->findOrFail($survey)->delete();

        return response()->noContent();
    }
}
