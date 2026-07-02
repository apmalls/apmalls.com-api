<?php

namespace App\Http\Requests\Product\Brand;

use Illuminate\Foundation\Http\FormRequest;

class ChangeBrandStatusRequest extends FormRequest
{
    /**
     * Authorize
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

            'is_active' => [
                'required',
                'boolean',
            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'is_active.required' => 'Status is required.',

            'is_active.boolean' => 'Status must be true or false.',

        ];
    }
}
