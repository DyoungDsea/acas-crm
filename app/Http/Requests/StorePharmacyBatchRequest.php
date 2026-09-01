<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePharmacyBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'batch_number' => ['required', 'string', 'max:100'],
            'quantity_received' => ['required', 'integer', 'min:1'],
            'expiry_date' => ['required', 'date', 'after:today'],
            'received_at' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in(['active', 'expired', 'depleted'])],
        ];
    }
}
