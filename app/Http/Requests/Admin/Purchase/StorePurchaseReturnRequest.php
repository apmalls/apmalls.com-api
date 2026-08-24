<?php

namespace App\Http\Requests\Admin\Purchase;

use App\Models\Purchase\PurchaseReturn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseReturnRequest extends FormRequest
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

            'purchase_order_id' => [
                'required',
                'integer',
                'exists:purchase_orders,id',
            ],

            'supplier_id' => [
                'required',
                'integer',
                'exists:suppliers,id',
            ],

            'return_no' => [
                'nullable',
                'string',
                'max:100',
                'unique:purchase_returns,return_no',
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
                'nullable',
                Rule::in(PurchaseReturn::getStatuses()),
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
                'integer',
                'exists:purchase_order_items,id',
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

            'items.*.purchase_price' => [
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
