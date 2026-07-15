<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\CustomerAddress;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerAddressRequest extends FormRequest
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

            'address_type' => [

                'required',

                'in:Home,Office,Billing,Shipping,Other',

            ],

            'contact_person' => [

                'nullable',

                'string',

                'max:100',

            ],

            'mobile' => [

                'nullable',

                'digits:10',

            ],

            'alternate_mobile' => [

                'nullable',

                'digits:10',

            ],

            'email' => [

                'nullable',

                'email',

            ],

            'address_line_1' => [

                'required',

                'string',

                'max:255',

            ],

            'address_line_2' => [

                'nullable',

                'string',

                'max:255',

            ],

            'landmark' => [

                'nullable',

                'string',

                'max:255',

            ],

            'city' => [

                'required',

                'string',

                'max:100',

            ],

            'state' => [

                'required',

                'string',

                'max:100',

            ],

            'country' => [

                'required',

                'string',

                'max:100',

            ],

            'postal_code' => [

                'required',

                'string',

                'max:10',

            ],

            'is_default' => [

                'sometimes',

                'boolean',

            ],

        ];
    }
}
