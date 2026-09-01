<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        return PropertyResource::collection(Property::with('units')->paginate($request->integer('per_page', 20)));
    }

    public function store(StorePropertyRequest $request)
    {
        return new PropertyResource(Property::create($request->validated()));
    }

    public function show(Property $property)
    {
        return new PropertyResource($property->load('units'));
    }

    public function update(StorePropertyRequest $request, Property $property)
    {
        $property->update($request->validated());

        return new PropertyResource($property);
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return response()->noContent();
    }
}
