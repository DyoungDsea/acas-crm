<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Deliberately separate from StoreProductRequest, not a variant of
// it — that request is shared by the generic Products API/page for
// every company type, with a free-form category_id and attributes
// array. A vehicle listing has a fixed, known shape (make, model,
// registration, year, condition) and a listing_type that determines
// which of the two auto-provisioned categories it lands in — see
// VehicleListingPageController's class comment. Reusing
// StoreProductRequest would mean either loosening its rules for
// everyone else or bolting vehicle-only fields onto a generic request.
class StoreVehicleListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listing_type' => ['required', Rule::in(['sale', 'rent'])],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:' . (date('Y') + 1)],
            'condition' => ['nullable', Rule::in(['brand_new', 'foreign_used', 'locally_used'])],
            'price' => ['required', 'numeric', 'min:0'],
            'vehicle_status' => ['required', Rule::in(['available', 'sold', 'rented', 'in_repair'])],
        ];
    }
}
