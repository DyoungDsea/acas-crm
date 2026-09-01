<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLivestockBatchRequest;
use App\Http\Resources\LivestockBatchResource;
use App\Models\LivestockBatch;
use Illuminate\Http\Request;

class LivestockBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = LivestockBatch::query();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return LivestockBatchResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreLivestockBatchRequest $request)
    {
        return new LivestockBatchResource(LivestockBatch::create($request->validated()));
    }

    public function show(LivestockBatch $livestockBatch)
    {
        return new LivestockBatchResource($livestockBatch);
    }

    public function update(StoreLivestockBatchRequest $request, LivestockBatch $livestockBatch)
    {
        $livestockBatch->update($request->validated());

        return new LivestockBatchResource($livestockBatch);
    }

    public function destroy(LivestockBatch $livestockBatch)
    {
        $livestockBatch->delete();

        return response()->noContent();
    }
}
