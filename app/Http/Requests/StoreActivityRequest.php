<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_type' => ['required', 'string'],
            'subject_id' => ['required', 'string'],
            'type' => ['required', 'string', Rule::in(['call', 'meeting', 'note', 'email'])],
            'description' => ['nullable', 'string'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
