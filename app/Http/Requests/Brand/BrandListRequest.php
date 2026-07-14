<?php

declare(strict_types=1);

namespace App\Http\Requests\Website\Brand;

use Illuminate\Foundation\Http\FormRequest;

class BrandListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

        ];
    }

    public function filters(): array
    {
        return [

            'search' => trim((string) $this->input('search')),

            'per_page' => (int) $this->input('per_page', 20),

        ];
    }
}
