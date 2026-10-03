<?php

namespace App\Http\Requests\Banner;

use App\Models\Banner\WebsiteBanner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebsiteBannerRequest extends FormRequest
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
        $id = $this->route('id');

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
                Rule::unique('website_banners', 'slug')->ignore($id),
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'desktop_image' => [
                'nullable',
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

            'display_mode' => [
                'sometimes',
                'required',
                Rule::in($this->input('type') === 'video'
                    ? [WebsiteBanner::DISPLAY_IMAGE_WITH_TEXT]
                    : [WebsiteBanner::DISPLAY_FULL_IMAGE, WebsiteBanner::DISPLAY_IMAGE_WITH_TEXT]),
            ],

            'banner_type' => [
                'sometimes',
                'required',
                Rule::in(['slider'])
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
                ...($this->filled('start_date') ? ['after_or_equal:start_date'] : [])
            ],

        ];
    }

    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator): void {
                if ($this->input('type') !== 'video' || $this->exists('display_mode')) {
                    return;
                }

                $banner = WebsiteBanner::find($this->route('id'));

                if ($banner?->display_mode === WebsiteBanner::DISPLAY_FULL_IMAGE) {
                    $validator->errors()->add(
                        'display_mode',
                        'Video banners must use the banner with text display mode.'
                    );
                }
            },
        ];
    }
}
