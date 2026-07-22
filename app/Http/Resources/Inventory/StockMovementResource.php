<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
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

            'reference_type' => $this->reference_type,

            'reference_id' => $this->reference_id,

            'movement_type' => $this->movement_type,

            'quantity' => $this->quantity,

            'stock_before' => $this->stock_before,

            'stock_after' => $this->stock_after,

            'remarks' => $this->remarks,

            'created_by' => $this->whenLoaded('creator'),

            'created_at' => $this->created_at,

        ];
    }
}
