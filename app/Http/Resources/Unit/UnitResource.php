<?php

declare(strict_types=1);

namespace App\Http\Resources\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
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

            'short_name' => $this->short_name,

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
