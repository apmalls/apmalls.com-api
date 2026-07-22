<?php

namespace App\Http\Requests\Admin\Purchase;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
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

            /*
            |--------------------------------------------------------------------------
            | Purchase
            |--------------------------------------------------------------------------
            */

            'supplier_id' => [
                'required',
                'integer',
                'exists:suppliers,id',
            ],

            'warehouse_id' => [
                'nullable',
                'integer',
                'exists:warehouses,id',
            ],

            'purchase_no' => [
                'nullable',
                'string',
                'max:50',
                'unique:purchase_orders,purchase_no',
            ],

            'invoice_no' => [
                'nullable',
                'string',
                'max:50',
            ],

            'purchase_date' => [
                'required',
                'date',
            ],

            'invoice_date' => [
                'nullable',
                'date',
                'after_or_equal:purchase_date',
            ],

            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */

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

            'shipping_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'other_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'round_off' => [
                'nullable',
                'numeric',
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

            'due_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'refund_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'payment_status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'partial',
                    'completed',
                    'refunded',
                ]),
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'ordered',
                    'partial',
                    'received',
                    'completed',
                    'cancelled',
                ]),
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Purchase Items
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
            ],

            'items.*.unit_id' => [
                'nullable',
                'exists:units,id',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'items.*.free_quantity' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items.*.unit_cost' => [
                'required',
                'numeric',
                'min:0',
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

            'supplier_id.required' => 'Supplier is required.',

            'purchase_date.required' => 'Purchase date is required.',

            'items.required' => 'At least one purchase item is required.',

            'items.*.product_id.required' => 'Product is required.',

            'items.*.quantity.required' => 'Quantity is required.',

            'items.*.unit_cost.required' => 'Unit cost is required.',

        ];
    }

    /**
     * Attribute Names
     */
    public function attributes(): array
    {
        return [

            'supplier_id' => 'supplier',

            'warehouse_id' => 'warehouse',

            'purchase_no' => 'purchase number',

            'invoice_no' => 'invoice number',

            'purchase_date' => 'purchase date',

            'invoice_date' => 'invoice date',

            'unit_cost' => 'unit cost',

            'line_total' => 'line total',

        ];
    }
}
