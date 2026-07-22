<?php

namespace App\Http\Requests\Admin\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'payment_date' => ['required', 'date'],

            'paymentable_type' => ['required', 'string'],

            'paymentable_id' => ['required', 'integer'],

            'payment_mode_id' => ['required', 'exists:payment_modes,id'],

            'customer_id' => ['nullable', 'exists:customers,id'],

            'supplier_id' => ['nullable', 'exists:suppliers,id'],

            'amount' => ['required', 'numeric', 'gt:0'],

            'transaction_no' => ['nullable', 'string', 'max:150'],

            'reference_no' => ['nullable', 'string', 'max:150'],

            'gateway' => ['nullable', 'string', 'max:50'],

            'remarks' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [

            'payment_date.required' => 'Payment date is required.',
            'payment_date.date' => 'Please enter a valid payment date.',

            'paymentable_type.required' => 'Payment type is required.',

            'paymentable_id.required' => 'Reference ID is required.',

            'payment_mode_id.required' => 'Please select a payment mode.',
            'payment_mode_id.exists' => 'Selected payment mode is invalid.',

            'customer_id.exists' => 'Selected customer is invalid.',

            'supplier_id.exists' => 'Selected supplier is invalid.',

            'amount.required' => 'Payment amount is required.',
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
            'paymentable_type' => 'payment type',
            'paymentable_id' => 'reference',
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
