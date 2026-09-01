<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDealRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $query = Deal::with(['contact', 'stage', 'owner']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return DealResource::collection($query->latest()->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreDealRequest $request)
    {
        $deal = Deal::create([...$request->validated(), 'owner_id' => $request->validated('owner_id') ?? $request->user()->id]);

        return new DealResource($deal->load(['contact', 'stage', 'owner']));
    }

    public function show(Deal $deal)
    {
        return new DealResource($deal->load(['contact', 'stage', 'owner', 'activities']));
    }

    public function update(StoreDealRequest $request, Deal $deal)
    {
        $deal->update($request->validated());

        return new DealResource($deal->load(['contact', 'stage', 'owner']));
    }

    public function destroy(Deal $deal)
    {
        $deal->delete();

        return response()->noContent();
    }
}
