<?php

namespace App\Http\Requests\Admin\Sale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Sale\SaleOrder;

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
                    SaleOrder::STATUS_DRAFT,
                    SaleOrder::STATUS_CONFIRMED,
                    SaleOrder::STATUS_COMPLETED,
                    SaleOrder::STATUS_CANCELLED,
                ])

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
