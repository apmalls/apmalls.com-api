<?php

namespace App\Http\Requests\Admin\POS;

use Illuminate\Foundation\Http\FormRequest;

class OpenSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'cash_register_id' => ['nullable', 'integer', 'exists:cash_registers,id'],

            'remarks' => ['nullable', 'string'],

        ];
    }
}
