<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolarProjectRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(['site_survey', 'installation', 'consultancy', 'solar_farm'])],
            'status' => ['required', 'string', Rule::in(['survey', 'quoted', 'installing', 'completed'])],
            'start_date' => ['nullable', 'date'],
            'completion_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }
}
