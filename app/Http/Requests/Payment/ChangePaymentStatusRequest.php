<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePaymentStatusRequest extends FormRequest
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

            'status' => [

                'required',

                Rule::in([

                    'Pending',

                    'Completed',

                    'Cancelled',

                    'Failed',

                ]),

            ],

        ];
    }

    /**
     * Custom Attributes.
     */
    public function attributes(): array
    {
        return [

            'status' => 'Payment Status',

        ];
    }

    /**
     * Custom Messages.
     */
    public function messages(): array
    {
        return [

            'status.required' => 'Please select payment status.',

            'status.in' => 'Invalid payment status selected.',

        ];
    }
}
