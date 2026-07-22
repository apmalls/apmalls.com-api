<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'sale_return_id' => $this->sale_return_id,

            'sale_order_item_id' => $this->sale_order_item_id,

            'product_id' => $this->product_id,

            'product' => $this->whenLoaded(
                'product'
            ),

            'selling_price' => $this->selling_price,

            'quantity' => $this->quantity,

            'line_total' => $this->line_total,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}
