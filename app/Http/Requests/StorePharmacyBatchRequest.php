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
        // 'after:today' only makes sense when receiving new stock —
        // editing an existing batch (e.g. to mark it depleted) must
        // still work once its expiry date has naturally passed,
        // which is exactly the case this field exists to track.
        $expiryRules = ['required', 'date'];
        if ($this->isMethod('post')) {
            $expiryRules[] = 'after:today';
        }

        return [
            'product_id' => ['required', 'exists:products,id'],
            'vendor_contact_id' => ['nullable', 'exists:contacts,id'],
            'batch_number' => ['required', 'string', 'max:100'],
            'quantity_received' => ['required', 'integer', 'min:1'],
            'expiry_date' => $expiryRules,
            'received_at' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in(['active', 'expired', 'depleted'])],
        ];
    }
}
