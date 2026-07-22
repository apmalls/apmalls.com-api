<?php

namespace App\Http\Requests\Admin\Purchase;

use App\Models\Purchase\PurchaseReturn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePurchaseReturnStatusRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [

            'status' => [
                'required',
                'string',
                Rule::in(
                    PurchaseReturn::getStatuses()
                ),
            ],

        ];
    }
}
