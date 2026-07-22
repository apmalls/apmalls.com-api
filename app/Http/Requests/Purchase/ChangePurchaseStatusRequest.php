<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Purchase\PurchaseOrder;

class ChangePurchaseStatusRequest extends FormRequest
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

            'status' => [

                'required',

                Rule::in(PurchaseOrder::statuses())

            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'status.required' => 'Purchase status is required.',

            'status.in' => 'Invalid purchase status.',

        ];
    }
}
