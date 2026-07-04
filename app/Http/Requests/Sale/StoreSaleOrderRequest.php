<?php

namespace App\Http\Requests\Sale;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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

            'customer_id' => [
                'required',
                'exists:customers,id',
            ],

            'invoice_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'sale_date' => [
                'required',
                'date',
            ],

            'sub_total' => [
                'required',
                'numeric',
                'min:0',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'tax_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'shipping_charge' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'other_charge' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'grand_total' => [
                'required',
                'numeric',
                'min:0',
            ],

            'paid_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'exists:products,id',
            ],

            'items.*.purchase_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'items.*.tax_percent' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items.*.tax_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items.*.discount_percent' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items.*.discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items.*.line_total' => [
                'required',
                'numeric',
                'min:0',
            ],

        ];
    }

    /**
     * Custom Messages
     */
    public function messages(): array
    {
        return [

            'customer_id.required' => 'Customer is required.',

            'customer_id.exists' => 'Selected customer is invalid.',

            'items.required' => 'At least one product is required.',

            'items.min' => 'At least one product is required.',

            'items.*.product_id.required' => 'Product is required.',

            'items.*.product_id.exists' => 'Selected product is invalid.',

            'items.*.quantity.required' => 'Quantity is required.',

            'items.*.quantity.min' => 'Quantity must be at least 1.',

            'items.*.selling_price.required' => 'Selling price is required.',

            'items.*.line_total.required' => 'Line total is required.',

        ];
    }
}
