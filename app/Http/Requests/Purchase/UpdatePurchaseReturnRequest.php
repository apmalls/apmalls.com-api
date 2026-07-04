<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseReturnRequest extends FormRequest
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

            'purchase_order_id' => [
                'required',
                'exists:purchase_orders,id',
            ],

            'supplier_id' => [
                'required',
                'exists:suppliers,id',
            ],

            'return_date' => [
                'required',
                'date',
            ],

            'total_amount' => [
                'required',
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

            'items.*.purchase_order_item_id' => [
                'required',
                'exists:purchase_order_items,id',
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

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'items.*.line_total' => [
                'required',
                'numeric',
                'min:0',
            ],

        ];
    }
}
