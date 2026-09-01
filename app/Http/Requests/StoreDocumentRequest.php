<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'documentable_type' => ['required', 'string'],
            'documentable_id' => ['required', 'string'],
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ];
    }
}
