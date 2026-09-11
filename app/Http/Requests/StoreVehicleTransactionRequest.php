<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Deliberately just the Order-creation fields now — no amount/method/
// reference_note here. Taking payment is Payments' job (it already
// enforces CompanyPaymentSetting against whatever Order it's pointed
// at), and this Order is no different from any other once it exists.
// Duplicating that validation here would mean two places to keep in
// sync with the same business rule.
class StoreVehicleTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_product_id' => ['required', 'exists:products,id'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'rental_start_at' => ['nullable', 'date'],
            'rental_end_at' => ['nullable', 'date', 'after_or_equal:rental_start_at'],
        ];
    }
}
