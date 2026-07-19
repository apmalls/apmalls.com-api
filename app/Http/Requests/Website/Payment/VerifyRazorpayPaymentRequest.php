<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Payment;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRazorpayPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'order_id' => [
                'required',
                'integer',
                'exists:sale_orders,id',
            ],

            'payment_mode_id' => [
                'required',
                'integer',
                'exists:payment_modes,id',
            ],

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
