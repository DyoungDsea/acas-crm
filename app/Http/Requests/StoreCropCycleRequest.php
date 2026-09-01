<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCropCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'crop_type' => ['required', 'string', 'max:100'],
            'plot_label' => ['required', 'string', 'max:100'],
            'planted_at' => ['required', 'date'],
            'expected_harvest_at' => ['nullable', 'date', 'after:planted_at'],
            'harvested_at' => ['nullable', 'date'],
            'yield_quantity' => ['nullable', 'numeric', 'min:0'],
            'yield_unit' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', Rule::in(['planted', 'growing', 'harvested', 'closed'])],
        ];
    }
}
