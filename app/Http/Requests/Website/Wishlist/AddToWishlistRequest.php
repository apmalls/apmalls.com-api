<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Wishlist;

use Illuminate\Foundation\Http\FormRequest;

class AddToWishlistRequest extends FormRequest
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

            'remarks' => [

                'nullable',

                'string',

                'max:500',

            ],

        ];
    }
}
