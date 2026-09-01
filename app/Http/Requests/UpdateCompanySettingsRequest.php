<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'allow_partial_payments' => ['required', 'boolean'],
            'require_full_payment' => ['required', 'boolean'],
            'minimum_partial_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
