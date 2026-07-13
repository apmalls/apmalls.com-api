<?php

namespace App\Http\Requests\Website\Category;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
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
     * Custom Messages
     */
    public function messages(): array
    {
        return [

            'per_page.integer' => 'Per page must be an integer.',

            'per_page.min' => 'Per page must be at least 1.',

            'per_page.max' => 'Per page may not be greater than 100.',

        ];
    }

    /**
     * Validated Filters
     */
    public function filters(): array
    {
        return [

            'search' => trim((string) $this->input('search')),

            'per_page' => (int) ($this->input('per_page', 20)),

        ];
    }
}
