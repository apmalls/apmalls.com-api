<?php

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [

            'product_id' => [
                'required',
                'exists:products,id',
            ],

            'physical_stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],

        ];
    }

    /**
     * Custom messages.
     */
    public function messages(): array
    {
        return [

            'product_id.required' => 'Product is required.',

            'product_id.exists' => 'Selected product does not exist.',

            'physical_stock.required' => 'Physical stock is required.',

            'physical_stock.integer' => 'Physical stock must be an integer.',

            'physical_stock.min' => 'Physical stock cannot be negative.',

        ];
    }
}
