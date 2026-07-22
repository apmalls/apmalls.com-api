<?php

namespace App\Http\Requests\Admin\Sale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleOrderRequest extends FormRequest
{
    /**
     * Authorize the request.
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
        $saleId = $this->route('sale');

        if (is_object($saleId)) {
            $saleId = $saleId->id;
        }

        return [

            /*
            |--------------------------------------------------------------------------
            | Sale
            |--------------------------------------------------------------------------
            */

            'customer_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:customers,id',
            ],

            'sale_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('sale_orders', 'sale_no')->ignore($saleId),
            ],

            'invoice_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('sale_orders', 'invoice_no')->ignore($saleId),
            ],

            'sale_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'invoice_date' => [
                'nullable',
                'date',
            ],

            'billing_address_id' => [
                'nullable',
                'integer',
                'exists:customer_addresses,id',
            ],

            'shipping_address_id' => [
                'nullable',
                'integer',
                'exists:customer_addresses,id',
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
                'sometimes',
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
                    'confirmed',
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
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'items.*.unit_id' => [
                'required',
                'integer',
                'exists:units,id',
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
            'items.required' => 'At least one sale item is required.',
            'items.min' => 'At least one sale item is required.',
        ];
    }

    /**
     * Custom Attributes
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'sale_no' => 'sale number',
            'invoice_no' => 'invoice number',
            'sale_date' => 'sale date',
            'invoice_date' => 'invoice date',
            'billing_address_id' => 'billing address',
            'shipping_address_id' => 'shipping address',
        ];
    }
}
