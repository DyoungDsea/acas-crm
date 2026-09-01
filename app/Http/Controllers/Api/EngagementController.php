<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEngagementRequest;
use App\Http\Resources\EngagementResource;
use App\Models\Engagement;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Engagement::with(['contact', 'leadAdvisor']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return EngagementResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreEngagementRequest $request)
    {
        return new EngagementResource(Engagement::create($request->validated())->load(['contact', 'leadAdvisor']));
    }

    public function show(Engagement $engagement)
    {
        return new EngagementResource($engagement->load(['contact', 'leadAdvisor']));
    }

    public function update(StoreEngagementRequest $request, Engagement $engagement)
    {
        $engagement->update($request->validated());

        return new EngagementResource($engagement->load(['contact', 'leadAdvisor']));
    }

    public function destroy(Engagement $engagement)
    {
        $engagement->delete();

        return response()->noContent();
    }
}
