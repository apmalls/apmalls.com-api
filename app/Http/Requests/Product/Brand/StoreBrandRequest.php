<?php

namespace App\Http\Requests\Product\Brand;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
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


            'name' => [
                'required',
                'string',
                'max:255',
                'unique:brands,name',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:brands,slug',
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
     * Get custom messages for validation errors.
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
