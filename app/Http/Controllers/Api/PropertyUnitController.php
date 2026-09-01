<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyUnitRequest;
use App\Http\Resources\PropertyUnitResource;
use App\Models\Property;

// Nested under /properties/{property}/units
class PropertyUnitController extends Controller
{
    public function index(Property $property)
    {
        return PropertyUnitResource::collection($property->units);
    }

    public function store(StorePropertyUnitRequest $request, Property $property)
    {
        return new PropertyUnitResource($property->units()->create($request->validated()));
    }

    public function update(StorePropertyUnitRequest $request, Property $property, string $unit)
    {
        $unitModel = $property->units()->findOrFail($unit);
        $unitModel->update($request->validated());

        return new PropertyUnitResource($unitModel);
    }

    public function destroy(Property $property, string $unit)
    {
        $property->units()->findOrFail($unit)->delete();

        return response()->noContent();
    }
}
