<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Payment;

use Illuminate\Foundation\Http\FormRequest;

class MakePaymentRequest extends FormRequest
{
    /**
     * Authorize Request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            'payment_mode_id' => [

                'required',

                'integer',

                'exists:payment_modes,id',

            ],

            'amount' => [

                'required',

                'numeric',

                'min:1',

            ],

            'transaction_no' => [

                'nullable',

                'string',

                'max:255',

            ],

            'reference_no' => [

                'nullable',

                'string',

                'max:255',

            ],

            'remarks' => [

                'nullable',

                'string',

                'max:1000',

            ],

        ];
    }
}
