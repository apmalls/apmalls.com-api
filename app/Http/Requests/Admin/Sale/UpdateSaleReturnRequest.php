<?php

namespace App\Http\Requests\Admin\Sale;

use App\Models\Sale\SaleReturn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleReturnRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [

            'sale_order_id' => [
                'required',
                'integer',
                'exists:sale_orders,id',
            ],

            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],

            'return_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('sale_returns', 'return_no')
                    ->ignore($this->route('id')),
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

            'status' => [
                'required',
                Rule::in(SaleReturn::getStatuses()),
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
                'integer',
                'exists:sale_order_items,id',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'items.*.selling_price' => [
                'required',
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
}
