<?php

namespace App\Http\Requests\Admin\PaymentMode;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [

            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('payment_modes', 'name')->ignore($id),
            ],

            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('payment_modes', 'code')->ignore($id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_online' => [
                'sometimes',
                'boolean',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

        ];
    }

    public function messages(): array
    {
        return [

            'name.unique' => 'Payment mode name already exists.',
            'code.unique' => 'Payment mode code already exists.',

            'is_online.boolean' => 'Online status must be true or false.',
            'is_active.boolean' => 'Active status must be true or false.',

            'sort_order.integer' => 'Sort order must be an integer.',
            'sort_order.min' => 'Sort order cannot be negative.',

        ];
    }

    public function attributes(): array
    {
        return [

            'name' => 'payment mode name',
            'code' => 'payment mode code',
            'description' => 'description',
            'icon' => 'icon',
            'is_online' => 'online status',
            'is_active' => 'active status',
            'sort_order' => 'sort order',

        ];
    }
}
