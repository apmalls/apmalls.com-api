<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleOrderItemResource extends JsonResource
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

            'unit_id' => $this->unit_id,

            'unit' => $this->whenLoaded('unit'),

            'quantity' => $this->quantity,

            'returned_quantity' => $this->returned_quantity,

            'pending_quantity' => $this->pending_quantity,

            'purchase_price' => $this->purchase_price,

            'selling_price' => $this->selling_price,

            'tax_percent' => $this->tax_percent,

            'tax_amount' => $this->tax_amount,

            'discount_percent' => $this->discount_percent,

            'discount_amount' => $this->discount_amount,

            'line_total' => $this->line_total,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}
