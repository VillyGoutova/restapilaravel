<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'min:2', 'max:100'],

            'price_min' => ['sometimes', 'numeric', 'min:0', 'max:999999999'],
            'price_max' => ['sometimes', 'numeric', 'min:0', 'max:999999999', 'gte:price_min'],

            'category_ids' => ['sometimes', 'array', 'max:10'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string', 'max:1000'],

            'include' => ['sometimes', 'in:categories'],
        ];
    }
}
