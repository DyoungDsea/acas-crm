<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $company = $this->route('company');
        $categoryId = $this->route('product_category')?->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('product_categories', 'name')
                    ->where(fn ($query) => $query->where('company_id', $company->id))
                    ->ignore($categoryId),
            ],
        ];
    }
}
