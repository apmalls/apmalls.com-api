<?php

namespace App\Http\Requests\Admin\Purchase;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
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
                'sometimes',
                'required',
                'integer',
                'exists:suppliers,id',
            ],

            'warehouse_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:warehouses,id',
            ],

            'purchase_no' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('purchase_orders', 'purchase_no')
                    ->ignore($this->route('purchase')),
            ],

            'invoice_no' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'purchase_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'invoice_date' => [
                'sometimes',
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
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'discount_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'tax_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'shipping_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'other_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'round_off' => [
                'sometimes',
                'nullable',
                'numeric',
            ],

            'grand_total' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'paid_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'due_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'refund_amount' => [
                'sometimes',
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
                'sometimes',
                Rule::in([
                    'pending',
                    'partial',
                    'completed',
                    'refunded',
                ]),
            ],

            'status' => [
                'sometimes',
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
                'sometimes',
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Purchase Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'sometimes',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required_with:items',
                'exists:products,id',
            ],

            'items.*.unit_id' => [
                'nullable',
                'exists:units,id',
            ],

            'items.*.quantity' => [
                'required_with:items',
                'numeric',
                'min:0.01',
            ],

            'items.*.free_quantity' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items.*.unit_cost' => [
                'required_with:items',
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
                'required_with:items',
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

            'items.min' => 'At least one purchase item is required.',

            'items.*.product_id.required_with' => 'Product is required.',

            'items.*.quantity.required_with' => 'Quantity is required.',

            'items.*.unit_cost.required_with' => 'Unit cost is required.',

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
