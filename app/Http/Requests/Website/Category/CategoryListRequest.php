<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class CategoryListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, ValidationRule|array|string>
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
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [

            'search.max' => 'Search may not be greater than 255 characters.',

            'per_page.integer' => 'Per page must be an integer.',

            'per_page.min' => 'Per page must be at least 1.',

            'per_page.max' => 'Per page may not be greater than 100.',

        ];
    }

    /**
     * Validated filters.
     */
    public function filters(): array
    {
        return [

            'search' => trim(
                (string) $this->input('search')
            ),

            'per_page' => (int) $this->input(
                'per_page',
                20
            ),

        ];
    }
}
