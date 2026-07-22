<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
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

            'system_stock' => $this->system_stock,

            'physical_stock' => $this->physical_stock,

            'difference' => $this->difference,

            'reason' => $this->reason,

            'created_by' => $this->whenLoaded('creator'),

            'updated_by' => $this->whenLoaded('updater'),

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}
