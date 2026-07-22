<?php

namespace App\Http\Requests\Admin\POS;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'cash_register_session_id' => [
                'required',
                'exists:cash_register_sessions,id'
            ],

            'customer_id' => [
                'required',
                'exists:customers,id'
            ],

            'payment_mode_id' => [
                'required',
                'exists:payment_modes,id'
            ],

            'paid_amount' => [
                'required',
                'numeric',
                'gt:0'
            ],

            'hold_id' => [
                'nullable',
                'exists:pos_holds,id'
            ],

            'items' => [
                'required',
                'array',
                'min:1'
            ],

            'remarks' => [
                'nullable',
                'string'
            ],

        ];
    }
}
