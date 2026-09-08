<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Sibling of StorePaymentRequest, not a replacement for it. That one
// is used by Api\PaymentController where the order comes from the
// URL (/orders/{order}/payments). This one is used by the Payments
// page, where the person picks the order from a dropdown, so
// order_id has to travel in the form body instead.
class StoreOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', Rule::in(['cash', 'transfer', 'pos', 'cheque'])],
            'reference_note' => ['nullable', 'string', 'max:255'],
            'received_at' => ['nullable', 'date'],
        ];
        // Balance / company payment-setting checks (require_full_payment,
        // minimum_partial_percent) live in PaymentPageController, same
        // reasoning as StorePaymentRequest's own comment — a static
        // rules() array can't see the order's remaining balance.
    }
}
