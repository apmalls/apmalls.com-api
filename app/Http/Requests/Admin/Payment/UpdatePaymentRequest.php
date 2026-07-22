<?php

namespace App\Http\Requests\Admin\Payment;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'payment_date' => ['sometimes', 'date'],

            'payment_mode_id' => ['sometimes', 'exists:payment_modes,id'],

            'customer_id' => ['nullable', 'exists:customers,id'],

            'supplier_id' => ['nullable', 'exists:suppliers,id'],

            'amount' => ['sometimes', 'numeric', 'gt:0'],

            'transaction_no' => ['nullable', 'string', 'max:150'],

            'reference_no' => ['nullable', 'string', 'max:150'],

            'gateway' => ['nullable', 'string', 'max:50'],

            'remarks' => ['nullable', 'string'],

        ];
    }

    public function messages(): array
    {
        return [

            'payment_date.date' => 'Please enter a valid payment date.',

            'payment_mode_id.exists' => 'Selected payment mode is invalid.',

            'customer_id.exists' => 'Selected customer is invalid.',

            'supplier_id.exists' => 'Selected supplier is invalid.',

            'amount.numeric' => 'Payment amount must be numeric.',

            'amount.gt' => 'Payment amount must be greater than zero.',

            'transaction_no.max' => 'Transaction number may not exceed 150 characters.',

            'reference_no.max' => 'Reference number may not exceed 150 characters.',

            'gateway.max' => 'Gateway name may not exceed 50 characters.',

            'remarks.string' => 'Remarks must be a valid text.',

        ];
    }

    public function attributes(): array
    {
        return [

            'payment_date' => 'payment date',

            'payment_mode_id' => 'payment mode',

            'customer_id' => 'customer',

            'supplier_id' => 'supplier',

            'amount' => 'payment amount',

            'transaction_no' => 'transaction number',

            'reference_no' => 'reference number',

            'gateway' => 'payment gateway',

            'remarks' => 'remarks',

        ];
    }
}
