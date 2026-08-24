<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    /**
     * Authorize request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

        ];
    }

    /**
     * Validation messages.
     */
    public function messages(): array
    {
        return [

            'quantity.required' => 'Quantity is required.',

            'quantity.min' => 'Quantity must be at least 1.',

        ];
    }

    /**
     * Filters.
     */
    public function filters(): array
    {
        return [

            'quantity' => 'trim|escape',

        ];
    }
}
