<?php

namespace App\Http\Requests\Admin\POS;

use Illuminate\Foundation\Http\FormRequest;

class CashInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'cash_register_session_id' => [
                'required',
                'integer',
                'exists:cash_register_sessions,id'
            ],

            'payment_mode_id' => [
                'required',
                'integer',
                'exists:payment_modes,id'
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0'
            ],

            'remarks' => [
                'nullable',
                'string'
            ],

        ];
    }
}
