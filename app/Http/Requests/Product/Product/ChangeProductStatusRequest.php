<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangeProductStatusRequest extends FormRequest
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

            'is_active' => [

                'required',

                'boolean',

            ],

        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [

            'is_active.required' => 'Status is required.',

            'is_active.boolean' => 'Status must be true or false.',

        ];
    }
}
