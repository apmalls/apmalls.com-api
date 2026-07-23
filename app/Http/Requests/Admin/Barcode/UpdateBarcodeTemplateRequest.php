<?php

namespace App\Http\Requests\Admin\Barcode;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBarcodeTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules.
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('barcode_templates', 'name')
                    ->ignore($id),
            ],

            'paper_size' => [
                'required',
                'string',
                'max:30',
            ],

            'width' => [
                'required',
                'integer',
                'min:1',
            ],

            'height' => [
                'required',
                'integer',
                'min:1',
            ],

            'font_size' => [
                'required',
                'integer',
                'between:6,30',
            ],

            'show_name' => [
                'required',
                'boolean',
            ],

            'show_price' => [
                'required',
                'boolean',
            ],

            'show_sku' => [
                'required',
                'boolean',
            ],

            'show_barcode' => [
                'required',
                'boolean',
            ],

            'show_qr' => [
                'required',
                'boolean',
            ],

            'status' => [
                'required',
                'boolean',
            ],

        ];
    }
}
