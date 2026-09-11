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
        // Same reasoning as StorePharmacyBatchRequest's expiry_date
        // split: a batch starts with quantity_current equal to
        // quantity_start — nothing has died or been sold yet — so
        // the page controller sets it on create rather than trusting
        // a second manually-typed number that could drift from the
        // first. On update it becomes a real, editable field (that's
        // how mortality/loss gets recorded), and 'lte:quantity_start'
        // stops it from being edited above where the batch started.
        $quantityCurrentRules = ['nullable', 'integer', 'min:0'];
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $quantityCurrentRules = ['required', 'integer', 'min:0', 'lte:quantity_start'];
        }

        return [
            'type' => ['required', 'string', Rule::in(['poultry', 'fishery'])],
            'batch_label' => ['required', 'string', 'max:100'],
            'quantity_start' => ['required', 'integer', 'min:1'],
            'quantity_current' => $quantityCurrentRules,
            'start_date' => ['required', 'date'],
            'expected_ready_at' => ['nullable', 'date', 'after:start_date'],
            'status' => ['required', 'string', Rule::in(['active', 'sold', 'closed'])],
        ];
    }
}
