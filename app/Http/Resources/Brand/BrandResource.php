<?php

declare(strict_types=1);

namespace App\Http\Resources\Brand;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'name' => $this->name,

            'slug' => $this->slug,

            'logo' => $this->logo,

            'logo_url' => $this->logo_url,

            'description' => $this->description,

            'sort_order' => $this->sort_order,

            'is_active' => (bool) $this->is_active,

            'is_featured' => (bool) $this->is_featured,

            'products_count' => $this->whenCounted('products'),

            'created_at' => $this->created_at?->format('d-m-Y H:i:s'),

            'updated_at' => $this->updated_at?->format('d-m-Y H:i:s'),

        ];

    }
}
