<?php

namespace App\Http\Requests\Product\Unit;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
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
        $id = $this->route('id');

        return [

            'name' => [

                'required',

                'string',

                'max:255',

                Rule::unique('units', 'name')->ignore($id),

            ],

            'short_name' => [

                'required',

                'string',

                'max:20',

                Rule::unique('units', 'short_name')->ignore($id),

            ],

            'description' => [

                'nullable',

                'string',

            ],

            'is_active' => [

                'required',

                'boolean',

            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'name.required' => 'Unit name is required.',

            'name.unique' => 'Unit name already exists.',

            'short_name.required' => 'Short name is required.',

            'short_name.unique' => 'Short name already exists.',

        ];
    }
}
