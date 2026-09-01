<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level 'company_admin' middleware handles authorization
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'allow_partial_payments' => ['required', 'boolean'],
            'require_full_payment' => ['required', 'boolean'],
            'minimum_partial_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
