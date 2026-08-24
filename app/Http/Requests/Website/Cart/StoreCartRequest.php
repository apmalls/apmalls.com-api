<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Cart;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartRequest extends FormRequest
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

            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

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

            'product_id.required' => 'Product is required.',

            'product_id.exists' => 'Selected product does not exist.',

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

            'product_id' => 'trim|escape',

            'quantity' => 'trim|escape',

        ];
    }
}
