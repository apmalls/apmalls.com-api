<?php

namespace App\Http\Requests\Admin\Sale;

use App\Models\Sale\SaleReturn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeSaleReturnStatusRequest extends FormRequest
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
                    SaleReturn::getStatuses()
                ),
            ],

        ];
    }
}
