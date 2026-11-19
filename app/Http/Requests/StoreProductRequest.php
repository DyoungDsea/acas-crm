<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $company = $this->route('company');

        return [
            // Required only when the user isn't typing a new category.
            // Also scoped to the company so nobody can attach another company's category.
            'category_id' => [
                'required_without:new_category_name',
                'nullable',
                Rule::exists('product_categories', 'id')->where('company_id', $company->id),
            ],
            'new_category_name' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'attributes' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required_without' => 'Select a category or add a new one.',
        ];
    }
}
