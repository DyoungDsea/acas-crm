<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleBookingRequest;
use App\Http\Resources\VehicleBookingResource;
use App\Models\Vehicle;

// Nested under /vehicles/{vehicle}/bookings
class VehicleBookingController extends Controller
{
    public function index(Vehicle $vehicle)
    {
        return VehicleBookingResource::collection($vehicle->bookings()->with('contact')->get());
    }

    public function store(StoreVehicleBookingRequest $request, Vehicle $vehicle)
    {
        $booking = $vehicle->bookings()->create($request->validated());
        $vehicle->update(['status' => 'rented']);

        return new VehicleBookingResource($booking->load('contact'));
    }

    public function update(StoreVehicleBookingRequest $request, Vehicle $vehicle, string $booking)
    {
        $bookingModel = $vehicle->bookings()->findOrFail($booking);
        $bookingModel->update($request->validated());

        if (in_array($bookingModel->status, ['completed', 'cancelled'])) {
            $vehicle->update(['status' => 'available']);
        }

        return new VehicleBookingResource($bookingModel->load('contact'));
    }

    public function destroy(Vehicle $vehicle, string $booking)
    {
        $vehicle->bookings()->findOrFail($booking)->delete();

        return response()->noContent();
    }
}
