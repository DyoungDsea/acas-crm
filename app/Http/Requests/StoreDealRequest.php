<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'pipeline_stage_id' => ['required', 'exists:pipeline_stages,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['open', 'won', 'lost'])],
            'owner_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
