<?php

namespace App\Http\Requests\Admin\POS;

use Illuminate\Foundation\Http\FormRequest;

class CloseSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'closing_balance' => ['required', 'numeric', 'min:0'],

            'remarks' => ['nullable', 'string'],

        ];
    }
}
