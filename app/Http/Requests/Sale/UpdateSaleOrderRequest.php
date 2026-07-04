<?php

namespace App\Http\Requests\Sale;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleOrderRequest extends FormRequest
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
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
            ],

            'tax_amount' => [
                'nullable',
                'numeric',
            ],

            'shipping_charge' => [
                'nullable',
                'numeric',
            ],

            'other_charge' => [
                'nullable',
                'numeric',
            ],

            'grand_total' => [
                'required',
                'numeric',
            ],

            'paid_amount' => [
                'nullable',
                'numeric',
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
            ],

            'items.*.selling_price' => [
                'required',
                'numeric',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'items.*.tax_percent' => [
                'nullable',
                'numeric',
            ],

            'items.*.tax_amount' => [
                'nullable',
                'numeric',
            ],

            'items.*.discount_percent' => [
                'nullable',
                'numeric',
            ],

            'items.*.discount_amount' => [
                'nullable',
                'numeric',
            ],

            'items.*.line_total' => [
                'required',
                'numeric',
            ],

        ];
    }
}
