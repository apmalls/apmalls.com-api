<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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

            'sku' => $this->sku,

            'barcode' => $this->barcode,

            'barcode_type' => $this->barcode_type,

            'is_barcode_auto' => (bool) $this->is_barcode_auto,

            'short_description' => $this->short_description,

            'description' => $this->description,

            'mrp' => $this->mrp,

            'selling_price' => $this->selling_price,

            'purchase_price' => $this->purchase_price,

            'discount_price' => $this->discount_price,

            'discount_percentage' => $this->discount_percentage,

            'tax_percent' => $this->tax_percent,

            'discount_percent' => $this->discount_percent,

            'minimum_stock' => $this->whenLoaded(
                'inventoryStock',
                fn () => $this->inventoryStock?->minimum_stock ?? $this->minimum_stock,
                $this->minimum_stock
            ),

            'thumbnail' => $this->thumbnail,

            'thumbnail_url' => $this->thumbnail_url,

            'stock' => $this->whenLoaded(
                'inventoryStock',
                fn () => $this->inventoryStock?->available_stock ?? 0,
                $this->stock
            ),

            'current_stock' => $this->whenLoaded(
                'inventoryStock',
                fn () => $this->inventoryStock?->current_stock
            ),

            'featured' => (bool) $this->featured,

            'new_arrival' => (bool) $this->new_arrival,

            'best_seller' => (bool) $this->best_seller,

            'is_active' => (bool) $this->is_active,

            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category' => $this->whenLoaded('category', function () {

                return [

                    'id' => $this->category->id,

                    'name' => $this->category->name,

                    'slug' => $this->category->slug,

                ];

            }),

            /*
            |--------------------------------------------------------------------------
            | Brand
            |--------------------------------------------------------------------------
            */

            'brand' => $this->whenLoaded('brand', function () {

                return [

                    'id' => $this->brand->id,

                    'name' => $this->brand->name,

                    'slug' => $this->brand->slug,

                ];

            }),

            /*
            |--------------------------------------------------------------------------
            | Unit
            |--------------------------------------------------------------------------
            */

            'unit' => $this->whenLoaded('unit', function () {

                return [

                    'id' => $this->unit->id,

                    'name' => $this->unit->name,

                    'short_name' => $this->unit->short_name,

                ];

            }),

            /*
            |--------------------------------------------------------------------------
            | Images
            |--------------------------------------------------------------------------
            */

            'images' => $this->whenLoaded('images', function () {

                return $this->images->map(function ($image) {

                    return [

                        'id' => $image->id,

                        'image' => $image->image,

                        'image_url' => $image->image_url,

                        'is_primary' => (bool) $image->is_primary,

                        'sort_order' => $image->sort_order,

                    ];

                });

            }),

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'created_at' => optional($this->created_at)
                ->format('d-m-Y H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->format('d-m-Y H:i:s'),

        ];

    }
}
