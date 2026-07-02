<?php

namespace App\Http\Requests\Product\Unit;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:units,name',

            'short_name' => 'required|string|max:20|unique:units,short_name',

            'description' => 'nullable|string',

            'is_active' => 'required|boolean',
        ];
    }

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
