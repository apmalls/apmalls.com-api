<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Sale;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
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

            'billing_address_id' => [

                'required',

                'integer',

                'exists:customer_addresses,id',

            ],

            'shipping_address_id' => [

                'required',

                'integer',

                'exists:customer_addresses,id',

            ],

            'remarks' => [

                'nullable',

                'string',

                'max:1000',

            ],

        ];
    }
}
