<?php

namespace App\Http\Requests\Admin\PaymentMode;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('payment_modes', 'name'),
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_modes', 'code'),
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
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
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

            'name.required' => 'Payment mode name is required.',
            'name.unique' => 'Payment mode name already exists.',

            'code.required' => 'Payment mode code is required.',
            'code.unique' => 'Payment mode code already exists.',

            'is_online.required' => 'Please specify whether the payment mode is online.',
            'is_online.boolean' => 'Online status must be true or false.',

            'is_active.required' => 'Please specify whether the payment mode is active.',
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
