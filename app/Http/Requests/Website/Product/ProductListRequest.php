<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductListRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Brand
            |--------------------------------------------------------------------------
            */

            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            'min_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'max_price' => [
                'nullable',
                'numeric',
                'gte:min_price',
            ],

            /*
            |--------------------------------------------------------------------------
            | Sort
            |--------------------------------------------------------------------------
            */

            'sort' => [
                'nullable',
                'in:latest,oldest,price_low_to_high,price_high_to_low,name_asc,name_desc',
            ],

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'category_id.exists' => 'Selected category is invalid.',

            'brand_id.exists' => 'Selected brand is invalid.',

            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price.',

            'per_page.max' => 'Maximum 100 records allowed per page.',

        ];
    }

    /**
     * Request Filters
     */
    public function filters(): array
    {
        return [

            'search' => trim((string) $this->input('search')),

            'category_id' => $this->input('category_id'),

            'brand_id' => $this->input('brand_id'),

            'min_price' => $this->input('min_price'),

            'max_price' => $this->input('max_price'),

            'sort' => $this->input('sort'),

            'per_page' => (int) $this->input('per_page', 20),

        ];
    }
}
