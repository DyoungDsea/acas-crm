<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_label' => ['required', 'string', 'max:255'],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['vacant', 'occupied'])],
        ];
    }
}
