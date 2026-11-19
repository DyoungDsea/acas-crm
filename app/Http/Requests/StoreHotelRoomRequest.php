<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Separate from StoreProductRequest for the same reason as
// StoreVehicleListingRequest: a room has a fixed shape (number, type,
// capacity, nightly rate) rather than the generic free-form product.
class StoreHotelRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_number' => ['required', 'string', 'max:30'],
            'room_type' => ['required', 'string', 'max:60'],
            'floor' => ['nullable', 'string', 'max:20'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'nightly_rate' => ['required', 'numeric', 'min:0'],
            'room_status' => ['required', Rule::in(['available', 'maintenance'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
