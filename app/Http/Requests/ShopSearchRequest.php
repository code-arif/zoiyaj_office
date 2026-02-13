<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => 'nullable|string|max:200',
            'per_page' => 'nullable|integer|min:1|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand_name' => 'nullable|string|max:100',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'min_discount' => 'nullable|numeric|min:0|max:100',
            'availability' => 'nullable|string|max:50',
            'sort_by' => 'nullable|string|in:name,price,created_at,original_price',
            'sort_order' => 'nullable|string|in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'query.max' => 'Search query cannot exceed 200 characters',
            'per_page.max' => 'Per page limit cannot exceed 100',
            'category_id.exists' => 'Selected category does not exist',
            'min_price.min' => 'Minimum price cannot be negative',
            'max_price.min' => 'Maximum price cannot be negative',
            'min_discount.max' => 'Minimum discount cannot exceed 100%',
            'sort_by.in' => 'Invalid sort field',
            'sort_order.in' => 'Sort order must be asc or desc',
        ];
    }
}
