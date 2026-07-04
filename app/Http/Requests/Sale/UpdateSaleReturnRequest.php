<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'sale_order_id' => [
                'required',
                'exists:sale_orders,id',
            ],

            'customer_id' => [
                'required',
                'exists:customers,id',
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

            'items.*.sale_order_item_id' => [
                'required',
                'exists:sale_order_items,id',
            ],

            'items.*.product_id' => [
                'required',
                'exists:products,id',
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

            'items.*.line_total' => [
                'required',
                'numeric',
                'min:0',
            ],

        ];
    }
}
