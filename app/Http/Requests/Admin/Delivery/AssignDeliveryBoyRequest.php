<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class AssignDeliveryBoyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [

            'sale_order_id' => [
                'required',
                'exists:sale_orders,id',
            ],

            'delivery_boy_id' => [
                'required',
                'exists:delivery_boys,id',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ];
    }
}
