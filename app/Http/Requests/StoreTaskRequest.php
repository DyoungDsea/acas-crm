<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'taskable_type' => ['nullable', 'string'],
            'taskable_id' => ['nullable', 'string', 'required_with:taskable_type'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'done'])],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }
}
