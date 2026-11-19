<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Status is only a real input when editing — a brand-new lot
        // is always active (the controller sets it), same way a new
        // pharmacy batch always starts fully in stock rather than
        // trusting a hand-typed starting state.
        $statusRules = ['nullable', Rule::in(['active', 'depleted'])];
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $statusRules = ['required', Rule::in(['active', 'depleted'])];
        }

        return [
            'product_id' => ['required', 'exists:products,id'],
            'vendor_contact_id' => ['nullable', 'exists:contacts,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'quantity_received' => ['required', 'integer', 'min:1'],
            'received_at' => ['required', 'date'],
            'status' => $statusRules,
        ];
    }
}
