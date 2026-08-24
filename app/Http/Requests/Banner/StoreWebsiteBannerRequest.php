<?php

namespace App\Http\Requests\Banner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebsiteBannerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
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

            'title' => [
                'required',
                'string',
                'max:255'
            ],

            'sub_title' => [
                'nullable',
                'string',
                'max:255'
            ],

            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('website_banners', 'slug'),
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'desktop_image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048'
            ],

            'mobile_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048'
            ],

            'type' => [
                'required',
                Rule::in(['image', 'video'])
            ],

            'banner_type' => [
                'required',
                Rule::in(['slider', 'offer'])
            ],

            'video_url' => [
                'required_if:type,video',
                'url'
            ],

            'position' => [
                'required',
                Rule::in([
                    'home_hero',
                    'home_top',
                    'home_middle',
                    'home_bottom',
                    'category',
                    'product',
                    'offer',
                    'popup'
                ])
            ],

            'button_text' => [
                'nullable',
                'string',
                'max:100'
            ],

            'button_url' => [
                'nullable',
                'url'
            ],

            'open_new_tab' => [
                'nullable',
                'boolean'
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0'
            ],

            'status' => [
                'nullable',
                'boolean'
            ],

            'start_date' => [
                'nullable',
                'date'
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date'
            ],

        ];
    }
}
