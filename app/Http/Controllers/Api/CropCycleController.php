<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCropCycleRequest;
use App\Http\Resources\CropCycleResource;
use App\Models\CropCycle;
use Illuminate\Http\Request;

class CropCycleController extends Controller
{
    public function index(Request $request)
    {
        $query = CropCycle::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return CropCycleResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreCropCycleRequest $request)
    {
        return new CropCycleResource(CropCycle::create($request->validated()));
    }

    public function show(CropCycle $cropCycle)
    {
        return new CropCycleResource($cropCycle);
    }

    public function update(StoreCropCycleRequest $request, CropCycle $cropCycle)
    {
        $cropCycle->update($request->validated());

        return new CropCycleResource($cropCycle);
    }

    public function destroy(CropCycle $cropCycle)
    {
        $cropCycle->delete();

        return response()->noContent();
    }
}
