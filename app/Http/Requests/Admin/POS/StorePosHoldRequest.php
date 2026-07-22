<?php

namespace App\Http\Requests\Admin\POS;

use Illuminate\Foundation\Http\FormRequest;

class StorePosHoldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'cash_register_session_id' => [
                'required',
                'exists:cash_register_sessions,id'
            ],

            'customer_id' => [
                'nullable',
                'exists:customers,id'
            ],

            'sub_total' => [
                'required',
                'numeric'
            ],

            'discount' => [
                'nullable',
                'numeric'
            ],

            'tax' => [
                'nullable',
                'numeric'
            ],

            'grand_total' => [
                'required',
                'numeric'
            ],

            'remarks' => [
                'nullable',
                'string'
            ],

            'items' => [
                'required',
                'array',
                'min:1'
            ],

            'items.*.product_id' => [
                'required',
                'exists:products,id'
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0'
            ],

            'items.*.price' => [
                'required',
                'numeric'
            ],

            'items.*.discount' => [
                'nullable',
                'numeric'
            ],

            'items.*.tax' => [
                'nullable',
                'numeric'
            ],

            'items.*.total' => [
                'required',
                'numeric'
            ],

        ];
    }
}
