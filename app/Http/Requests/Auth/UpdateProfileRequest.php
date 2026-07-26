<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Authorize
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | User
            |--------------------------------------------------------------------------
            */

            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'username' => [

                'nullable',

                'string',

                'max:100',

                Rule::unique('users')
                    ->ignore($this->user()),

            ],

            'email' => [

                'required',

                'email',

                Rule::unique('users')
                    ->ignore($this->user()),

            ],

            'mobile' => [

                'required',

                'digits:10',

                Rule::unique('users')
                    ->ignore($this->user()),

            ],

            'profile_photo' => [

                'nullable',

                'image',

                'mimes:jpg,jpeg,png,webp',

                'max:2048',

            ],

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer_type' => [
                'nullable',
                Rule::in([
                    'Retail',
                    'Wholesale',
                    'Walk-in',
                ]),
            ],

            'alternate_mobile' => [
                'nullable',
                'digits:10',
            ],

            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'gst_number' => [
                'nullable',
                'string',
                'max:20',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],

            'anniversary_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Customer Address
            |--------------------------------------------------------------------------
            */

            'address_id' => [
                'nullable',
                'integer',
                'exists:customer_addresses,id',
            ],

            'address_type' => [

                'nullable',

                Rule::in([
                    'Home',
                    'Office',
                    'Billing',
                    'Shipping',
                    'Other',
                ]),

            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address_mobile' => [
                'nullable',
                'digits:10',
            ],

            'address_alternate_mobile' => [
                'nullable',
                'digits:10',
            ],

            'address_email' => [
                'nullable',
                'email',
            ],

            'address_line_1' => [
                'nullable',
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
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'country' => [
                'nullable',
                'string',
                'max:100',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:10',
            ],

            'is_default' => [
                'nullable',
                'boolean',
            ],

        ];
    }
}
