<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    /**
     * Authorize Request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            'quantity' => [

                'required',

                'integer',

                'min:1',

            ],

        ];
    }
}
