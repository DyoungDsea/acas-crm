<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePharmacyBatchRequest;
use App\Http\Resources\PharmacyBatchResource;
use App\Models\PharmacyBatch;
use Illuminate\Http\Request;

class PharmacyBatchController extends Controller
{
    public function index(Request $request)
    {
        // Default ordering by expiry date supports FEFO stock rotation.
        return PharmacyBatchResource::collection(
            PharmacyBatch::with('product')->orderBy('expiry_date')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StorePharmacyBatchRequest $request)
    {
        $batch = PharmacyBatch::create([
            ...$request->validated(),
            'quantity_remaining' => $request->validated('quantity_received'),
        ]);

        return new PharmacyBatchResource($batch->load('product'));
    }

    public function show(PharmacyBatch $pharmacyBatch)
    {
        return new PharmacyBatchResource($pharmacyBatch->load('product'));
    }

    public function update(StorePharmacyBatchRequest $request, PharmacyBatch $pharmacyBatch)
    {
        $pharmacyBatch->update($request->validated());

        return new PharmacyBatchResource($pharmacyBatch->load('product'));
    }

    public function destroy(PharmacyBatch $pharmacyBatch)
    {
        $pharmacyBatch->delete();

        return response()->noContent();
    }
}
