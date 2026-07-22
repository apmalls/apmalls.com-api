<?php

namespace App\Http\Requests\Admin\Payment;

use App\Models\Payment\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'status' => [

                'required',

                Rule::in([

                    Payment::STATUS_PENDING,
                    Payment::STATUS_COMPLETED,
                    Payment::STATUS_FAILED,
                    Payment::STATUS_CANCELLED,
                    Payment::STATUS_REFUNDED,

                ]),

            ],

        ];
    }

    public function messages(): array
    {
        return [

            'status.required' => 'Payment status is required.',

            'status.in' => 'Selected payment status is invalid.',

        ];
    }

    public function attributes(): array
    {
        return [

            'status' => 'payment status',

        ];
    }
}
