<?php

namespace App\Http\Resources\Banner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebsiteBannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'title' => $this->title,

            'sub_title' => $this->sub_title,

            'slug' => $this->slug,

            'description' => $this->description,

            'desktop_image' => $this->desktop_image,

            'mobile_image' => $this->mobile_image,

            'type' => $this->type,

            'banner_type' => $this->banner_type,

            'video_url' => $this->video_url,

            'position' => $this->position,

            'button_text' => $this->button_text,

            'button_url' => $this->button_url,

            'open_new_tab' => $this->open_new_tab,

            'sort_order' => $this->sort_order,

            'status' => $this->status,

            'start_date' => optional($this->start_date)->format('Y-m-d H:i:s'),

            'end_date' => optional($this->end_date)->format('Y-m-d H:i:s'),

            'created_by' => $this->created_by,

            'updated_by' => $this->updated_by,

            'created_by_name' => $this->whenLoaded(
                'createdBy',
                fn () => $this->createdBy?->full_name
            ),

            'updated_by_name' => $this->whenLoaded(
                'updatedBy',
                fn () => $this->updatedBy?->full_name
            ),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
