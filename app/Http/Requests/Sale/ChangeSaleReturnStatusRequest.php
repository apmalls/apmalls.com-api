<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeSaleReturnStatusRequest extends FormRequest
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
                    'Draft',
                    'Approved',
                    'Completed',
                    'Cancelled',
                ]),

            ],

        ];
    }

    public function messages(): array
    {
        return [

            'status.required' => 'Status is required.',

            'status.in' => 'Invalid sale return status.',

        ];
    }
}
