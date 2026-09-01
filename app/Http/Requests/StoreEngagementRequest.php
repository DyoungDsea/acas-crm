<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEngagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['investment', 'management', 'consultancy'])],
            'scope' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['active', 'completed', 'cancelled'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'lead_advisor' => ['nullable', 'exists:users,id'],
        ];
    }
}
