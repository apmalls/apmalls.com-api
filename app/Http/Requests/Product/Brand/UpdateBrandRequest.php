<?php

namespace App\Http\Requests\Product\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    /**
     * Authorize
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
        $id = $this->route('id');

        return [

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->ignore($id),
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('brands', 'slug')->ignore($id),
            ],

            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
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

            'name.required' => 'Brand name is required.',

            'name.unique' => 'Brand name already exists.',

            'slug.unique' => 'Slug already exists.',

            'logo.image' => 'Logo must be an image.',

            'logo.mimes' => 'Logo must be a jpg, jpeg, png or webp file.',

            'logo.max' => 'Logo size must not exceed 2 MB.',

        ];
    }
}
