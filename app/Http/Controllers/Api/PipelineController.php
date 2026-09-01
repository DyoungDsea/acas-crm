<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePipelineRequest;
use App\Http\Resources\PipelineResource;
use App\Models\Pipeline;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    public function index()
    {
        return PipelineResource::collection(Pipeline::with('stages')->get());
    }

    public function store(StorePipelineRequest $request)
    {
        $pipeline = Pipeline::create($request->safe()->except('stages'));

        foreach ($request->validated('stages', []) as $i => $stage) {
            $pipeline->stages()->create([
                'name' => $stage['name'],
                'order' => $stage['order'] ?? $i,
            ]);
        }

        return new PipelineResource($pipeline->load('stages'));
    }

    public function show(Pipeline $pipeline)
    {
        return new PipelineResource($pipeline->load('stages'));
    }

    public function update(StorePipelineRequest $request, Pipeline $pipeline)
    {
        $pipeline->update($request->safe()->except('stages'));

        return new PipelineResource($pipeline->load('stages'));
    }

    public function destroy(Pipeline $pipeline)
    {
        $pipeline->delete();

        return response()->noContent();
    }

    // Nested stage management — kept here rather than a separate
    // controller since stages never exist outside a pipeline.
    public function addStage(Request $request, Pipeline $pipeline)
    {
        $request->validate(['name' => 'required|string|max:255', 'order' => 'nullable|integer']);

        $stage = $pipeline->stages()->create($request->only('name', 'order'));

        return response()->json(['id' => $stage->id, 'name' => $stage->name, 'order' => $stage->order], 201);
    }

    public function removeStage(Pipeline $pipeline, string $stageId)
    {
        $pipeline->stages()->findOrFail($stageId)->delete();

        return response()->noContent();
    }
}
