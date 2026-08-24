<?php

namespace App\Http\Requests\Admin\Barcode;

use Illuminate\Foundation\Http\FormRequest;

class PrintBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'barcode_template_id' => [
                'required',
                'exists:barcode_templates,id',
            ],

            'products' => [
                'required',
                'array',
                'min:1',
            ],

            'products.*.product_id' => [
                'required',
                'exists:products,id',
            ],

            'products.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

        ];
    }
}
