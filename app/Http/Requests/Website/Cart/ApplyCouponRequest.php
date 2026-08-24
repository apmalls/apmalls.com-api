<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Cart;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
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

            'coupon_code' => [
                'required',
                'string',
                'max:100',
            ],

        ];
    }

    /**
     * Validation messages.
     */
    public function messages(): array
    {
        return [

            'coupon_code.required' => 'Coupon code is required.',

        ];
    }

    /**
     * Filters.
     */
    public function filters(): array
    {
        return [

            'coupon_code' => 'trim|uppercase|escape',

        ];
    }
}
