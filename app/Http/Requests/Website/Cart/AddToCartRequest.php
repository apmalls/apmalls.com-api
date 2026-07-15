<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    /**
     * Authorize Request
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
}
