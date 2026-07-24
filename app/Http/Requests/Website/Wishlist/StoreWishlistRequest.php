<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Wishlist;

use Illuminate\Foundation\Http\FormRequest;

class StoreWishlistRequest extends FormRequest
{
    /**
     * Authorize request.
     */
    public function authorize(): bool
    {
        return auth('customer')->check();
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

            'remarks' => [
                'nullable',
                'string',
                'max:255',
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

        ];
    }

    /**
     * Filters.
     */
    public function filters(): array
    {
        return [

            'remarks' => 'trim|escape',

        ];
    }
}
