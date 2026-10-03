<?php

namespace App\Http\Requests\Admin\Barcode;

use Illuminate\Foundation\Http\FormRequest;

class DeleteBarcodeTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirmation_name' => [
                'bail',
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation_name.required' => 'Enter the template name to confirm permanent deletion.',
        ];
    }
}
