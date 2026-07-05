<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules.
     */
    public function rules(): array
    {
        return [

            'payment_mode_id' => [

                'required',

                'exists:payment_modes,id',

            ],

            'payment_date' => [

                'required',

                'date',

            ],

            'amount' => [

                'required',

                'numeric',

                'gt:0',

            ],

            'transaction_no' => [

                'nullable',

                'string',

                'max:100',

            ],

            'reference_no' => [

                'nullable',

                'string',

                'max:100',

            ],

            'remarks' => [

                'nullable',

                'string',

                'max:1000',

            ],

        ];
    }

    /**
     * Custom Attributes.
     */
    public function attributes(): array
    {
        return [

            'payment_mode_id' => 'Payment Mode',

            'payment_date' => 'Payment Date',

            'transaction_no' => 'Transaction Number',

            'reference_no' => 'Reference Number',

        ];
    }

    /**
     * Custom Messages.
     */
    public function messages(): array
    {
        return [

            'amount.gt' => 'Payment amount must be greater than zero.',

        ];
    }
}
