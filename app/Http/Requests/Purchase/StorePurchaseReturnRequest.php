<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Purchase\PurchaseOrderItem;
use Illuminate\Validation\Validator;

class StorePurchaseReturnRequest extends FormRequest
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

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'purchase_order_id.required' => 'Purchase order is required.',

            'purchase_order_id.exists' => 'Selected purchase order is invalid.',

            'supplier_id.required' => 'Supplier is required.',

            'supplier_id.exists' => 'Selected supplier is invalid.',

            'items.required' => 'At least one product is required.',

            'items.min' => 'At least one product is required.',

            'items.*.product_id.required' => 'Product is required.',

            'items.*.quantity.required' => 'Quantity is required.',

            'items.*.quantity.min' => 'Quantity must be at least 1.',

        ];
    }


    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {

            foreach ($this->items as $index => $item) {

                $purchaseItem = PurchaseOrderItem::with('purchaseReturnItems')
                    ->find($item['purchase_order_item_id']);

                if (!$purchaseItem) {

                    continue;

                }

                /*
                |--------------------------------------------------------------------------
                | Already Returned Quantity
                |--------------------------------------------------------------------------
                */

                $returnedQuantity = $purchaseItem
                    ->purchaseReturnItems()
                    ->whereHas('purchaseReturn', function ($query) {

                        $query->where('status', 'Completed');

                    })
                    ->sum('quantity');

                /*
                |--------------------------------------------------------------------------
                | Available Quantity
                |--------------------------------------------------------------------------
                */

                $availableQuantity =
                    $purchaseItem->received_quantity - $returnedQuantity;

                /*
                |--------------------------------------------------------------------------
                | Validation
                |--------------------------------------------------------------------------
                */

                if ($item['quantity'] > $availableQuantity) {

                    $validator->errors()->add(

                        "items.$index.quantity",

                        "Available return quantity is {$availableQuantity}."

                    );

                }

            }

        });
    }
}
