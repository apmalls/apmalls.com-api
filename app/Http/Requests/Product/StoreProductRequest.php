<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'brand_id' => [
                'nullable',
                'exists:brands,id',
            ],

            'unit_id' => [
                'required',
                'exists:units,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                'unique:products,name',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:products,slug',
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',
                'unique:products,barcode',
            ],

            'hsn_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            'thumbnail' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'images' => [
                'nullable',
                'array',
            ],

            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'short_description' => [
                'nullable',
                'string',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'purchase_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'selling_price' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'mrp' => [
                'required',
                'numeric',
                'gte:selling_price',
            ],

            'tax_percent' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],

            'discount_percent' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],

            'stock' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'minimum_stock' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'featured' => [
                'nullable',
                'boolean',
            ],

            'new_arrival' => [
                'nullable',
                'boolean',
            ],

            'best_seller' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

        ];
    }

    /**
     * Custom Validation Messages
     */
    public function messages(): array
    {
        return [

            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category is invalid.',

            'brand_id.exists' => 'Selected brand is invalid.',

            'unit_id.required' => 'Unit is required.',
            'unit_id.exists' => 'Selected unit is invalid.',

            'name.required' => 'Product name is required.',
            'name.unique' => 'Product name already exists.',

            'slug.unique' => 'Slug already exists.',

            'barcode.unique' => 'Barcode already exists.',

            'thumbnail.image' => 'Thumbnail must be an image.',
            'thumbnail.mimes' => 'Thumbnail must be jpg, jpeg, png or webp.',
            'thumbnail.max' => 'Thumbnail size must not exceed 2 MB.',

            'images.array' => 'Gallery images must be an array.',

            'images.*.image' => 'Each gallery file must be an image.',
            'images.*.mimes' => 'Gallery image must be jpg, jpeg, png or webp.',
            'images.*.max' => 'Each gallery image size must not exceed 2 MB.',

            'purchase_price.required' => 'Purchase price is required.',

            'selling_price.required' => 'Selling price is required.',

            'mrp.required' => 'MRP is required.',

            'is_active.required' => 'Status is required.',

        ];
    }
}
