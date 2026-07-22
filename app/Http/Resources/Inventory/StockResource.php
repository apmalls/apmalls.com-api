<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'product_id' => $this->product_id,

            'product' => $this->whenLoaded('product'),

            'current_stock' => $this->current_stock,

            'reserved_stock' => $this->reserved_stock,

            'available_stock' => $this->available_stock,

            'minimum_stock' => $this->minimum_stock,

            'maximum_stock' => $this->maximum_stock,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}
