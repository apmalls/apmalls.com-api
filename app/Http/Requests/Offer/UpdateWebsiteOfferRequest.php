<?php

namespace App\Http\Requests\Offer;

use App\Models\Offer\WebsiteOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebsiteOfferRequest extends FormRequest
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
                Rule::unique('website_offers', 'slug')->ignore($id),
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
                    ? [WebsiteOffer::DISPLAY_IMAGE_WITH_TEXT]
                    : [WebsiteOffer::DISPLAY_FULL_IMAGE, WebsiteOffer::DISPLAY_IMAGE_WITH_TEXT]),
            ],

            'banner_type' => ['prohibited'],

            'video_url' => [
                'required_if:type,video',
                'url'
            ],

            'position' => ['sometimes', 'required', 'string', 'max:255'],

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
                'sometimes',
                'required',
                'boolean'
            ],

            'sort_order' => [
                'sometimes',
                'required',
                'integer',
                'min:0'
            ],

            'status' => [
                'sometimes',
                'required',
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
                if ($validator->errors()->has('start_date') || $validator->errors()->has('end_date')) return;
                $offer = WebsiteOffer::find($this->route('id'));
                $start = $this->exists('start_date') ? $this->input('start_date') : $offer?->getRawOriginal('start_date');
                $end = $this->exists('end_date') ? $this->input('end_date') : $offer?->getRawOriginal('end_date');
                $timezone = config('app.business_timezone');
                if ($start && $end && \Illuminate\Support\Carbon::parse($end, $timezone)->lt(\Illuminate\Support\Carbon::parse($start, $timezone))) {
                    $validator->errors()->add('end_date', 'The end date must be on or after the start date.');
                }
            },
            function (\Illuminate\Validation\Validator $validator): void {
                if ($this->input('type') !== 'video' || $this->exists('display_mode')) {
                    return;
                }

                $offer = WebsiteOffer::find($this->route('id'));

                if ($offer?->display_mode === WebsiteOffer::DISPLAY_FULL_IMAGE) {
                    $validator->errors()->add(
                        'display_mode',
                        'Video offers must use the offer with text display mode.'
                    );
                }
            },
        ];
    }
}
