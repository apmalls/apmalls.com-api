<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeSaleStatusRequest extends FormRequest
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
                    'Confirmed',
                    'Completed',
                    'Cancelled',
                ]),

            ],

        ];
    }

    /**
     * Custom Messages
     */
    public function messages(): array
    {
        return [

            'status.required' => 'Status is required.',

            'status.in' => 'Invalid sale status selected.',

        ];
    }
}
