<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['residential', 'commercial', 'land'])],
            'address' => ['required', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::in(['available', 'leased', 'sold'])],
        ];
    }
}
