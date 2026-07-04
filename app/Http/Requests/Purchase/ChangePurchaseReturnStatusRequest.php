<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePurchaseReturnStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
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

            'status' => [

                'required',

                Rule::in([
                    'Draft',
                    'Approved',
                    'Completed',
                    'Cancelled',
                ]),

            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'status.required' => 'Status is required.',

            'status.in' => 'Invalid purchase return status.',

        ];
    }
}
