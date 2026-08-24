<?php

declare(strict_types=1);

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'product_id' => $this->product_id,

            'product_name' => $this->product?->name,

            'product_slug' => $this->product?->slug,

            'product_image' => $this->product?->image_url,

            'quantity' => (int) $this->quantity,

            'price' => (float) $this->price,

            'discount_percent' => (float) $this->discount_percent,

            'discount_amount' => (float) $this->discount_amount,

            'tax_percent' => (float) $this->tax_percent,

            'tax_amount' => (float) $this->tax_amount,

            'subtotal' => (float) $this->subtotal,

        ];
    }
}
