<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_unit_id' => ['required', 'exists:property_units,id'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['active', 'ended', 'terminated'])],
        ];
    }
}
