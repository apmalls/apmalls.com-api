<?php

namespace App\Http\Requests\Website\Payment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'razorpay_order_id' => [
                'required',
                'string',
            ],

            'razorpay_payment_id' => [
                'required',
                'string',
            ],

            'razorpay_signature' => [
                'required',
                'string',
            ],

        ];
    }

}
