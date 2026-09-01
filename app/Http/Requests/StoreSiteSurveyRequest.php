<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'survey_date' => ['required', 'date'],
            'findings' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['pending', 'completed'])],
        ];
    }
}
