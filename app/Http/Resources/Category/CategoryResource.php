<?php

declare(strict_types=1);

namespace App\Http\Resources\Category;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'parent_id' => $this->parent_id,

            'name' => $this->name,

            'slug' => $this->slug,

            'image' => $this->image,

            'image_url' => $this->image_url,

            'description' => $this->description,

            'sort_order' => $this->sort_order,

            'is_active' => (bool) $this->is_active,

            'is_featured' => (bool) $this->is_featured,

            'products_count' => $this->when(
                array_key_exists('products_count', $this->resource->getAttributes()),
                (int) ($this->products_count ?? 0) +
                    (int) ($this->descendant_products_count ?? 0)
            ),

            'direct_products_count' => $this->when(
                array_key_exists('products_count', $this->resource->getAttributes()),
                (int) ($this->products_count ?? 0)
            ),

            'parent' => $this->whenLoaded('parent', function () {

                return [

                    'id' => $this->parent->id,

                    'name' => $this->parent->name,

                    'slug' => $this->parent->slug,

                    'image_url' => $this->parent->image_url,

                ];

            }),

            'children' => $this->whenLoaded(
                'children',
                fn() => CategoryResource::collection($this->children)
            ),

            'created_at' => $this->created_at?->format('d-m-Y H:i:s'),

            'updated_at' => $this->updated_at?->format('d-m-Y H:i:s'),

        ];

    }
}
