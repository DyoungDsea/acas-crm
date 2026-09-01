<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSolarProjectRequest;
use App\Http\Resources\SolarProjectResource;
use App\Models\SolarProject;
use Illuminate\Http\Request;

class SolarProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = SolarProject::with('contact');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return SolarProjectResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreSolarProjectRequest $request)
    {
        return new SolarProjectResource(SolarProject::create($request->validated())->load('contact'));
    }

    public function show(SolarProject $solarProject)
    {
        return new SolarProjectResource($solarProject->load(['contact', 'siteSurveys.surveyor']));
    }

    public function update(StoreSolarProjectRequest $request, SolarProject $solarProject)
    {
        $solarProject->update($request->validated());

        return new SolarProjectResource($solarProject->load('contact'));
    }

    public function destroy(SolarProject $solarProject)
    {
        $solarProject->delete();

        return response()->noContent();
    }
}
