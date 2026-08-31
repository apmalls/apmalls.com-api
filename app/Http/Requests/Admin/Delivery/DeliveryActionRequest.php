<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:1000'],
            'cash_collected' => ['sometimes', 'boolean'],
        ];
    }
}
