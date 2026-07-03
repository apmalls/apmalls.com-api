<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Purchase
            |--------------------------------------------------------------------------
            */

            'supplier_id' => [
                'required',
                'exists:suppliers,id',
            ],

            'invoice_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'purchase_date' => [
                'required',
                'date',
            ],

            'invoice_date' => [
                'nullable',
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

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'exists:products,id',
                'distinct',
            ],

            'items.*.purchase_price' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'items.*.selling_price' => [
                'nullable',
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

            'items.*.discount_percent' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items.*.line_total' => [
                'required',
                'numeric',
                'gt:0',
            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'supplier_id.required' => 'Supplier is required.',
            'supplier_id.exists' => 'Selected supplier does not exist.',

            'purchase_date.required' => 'Purchase date is required.',

            'grand_total.required' => 'Grand total is required.',

            'items.required' => 'Please add at least one product.',

            'items.array' => 'Items must be an array.',

            'items.min' => 'At least one item is required.',

            'items.*.product_id.required' => 'Product is required.',

            'items.*.product_id.exists' => 'Selected product does not exist.',

            'items.*.product_id.distinct' => 'Duplicate product is not allowed.',

            'items.*.purchase_price.required' => 'Purchase price is required.',

            'items.*.quantity.required' => 'Quantity is required.',

            'items.*.quantity.min' => 'Quantity must be at least 1.',

            'items.*.line_total.required' => 'Line total is required.',

        ];
    }
}
