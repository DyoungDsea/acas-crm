<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', Rule::in(['cash', 'transfer', 'pos', 'cheque'])],
            'reference_note' => ['nullable', 'string', 'max:255'],
            'received_at' => ['nullable', 'date'],
        ];
        // The company's minimum-partial-percent / require-full-payment
        // rules are enforced in PaymentController, not here — they
        // depend on the order's remaining balance and the parent
        // company's settings, which a static rules() array can't see.
    }
}
