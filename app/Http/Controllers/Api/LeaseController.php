<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaseRequest;
use App\Http\Resources\LeaseResource;
use App\Models\Lease;
use Illuminate\Http\Request;

class LeaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Lease::with(['propertyUnit', 'contact']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return LeaseResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreLeaseRequest $request)
    {
        $lease = Lease::create($request->validated());
        $lease->propertyUnit->update(['status' => 'occupied']);

        return new LeaseResource($lease->load(['propertyUnit', 'contact']));
    }

    public function show(Lease $lease)
    {
        return new LeaseResource($lease->load(['propertyUnit', 'contact']));
    }

    public function update(StoreLeaseRequest $request, Lease $lease)
    {
        $lease->update($request->validated());

        if (in_array($lease->status, ['ended', 'terminated'])) {
            $lease->propertyUnit->update(['status' => 'vacant']);
        }

        return new LeaseResource($lease->load(['propertyUnit', 'contact']));
    }

    public function destroy(Lease $lease)
    {
        $lease->delete();

        return response()->noContent();
    }
}
