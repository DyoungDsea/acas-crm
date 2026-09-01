<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLivestockBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['poultry', 'fishery'])],
            'batch_label' => ['required', 'string', 'max:100'],
            'quantity_start' => ['required', 'integer', 'min:1'],
            'quantity_current' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'expected_ready_at' => ['nullable', 'date', 'after:start_date'],
            'status' => ['required', 'string', Rule::in(['active', 'sold', 'closed'])],
        ];
    }
}
