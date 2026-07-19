<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CreateRazorpayOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'order_id' => [
                'required',
                'integer',
                'exists:sale_orders,id',
            ],

        ];
    }
}
