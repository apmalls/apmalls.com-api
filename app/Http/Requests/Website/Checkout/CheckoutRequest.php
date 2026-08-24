<?php

namespace App\Http\Requests\Website\Checkout;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
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

            'payment_mode_id' => [
                'required',
                'integer',
                'exists:payment_modes,id',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:500',
            ],

        ];
    }
}
