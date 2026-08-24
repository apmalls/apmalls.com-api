<?php

namespace App\Http\Resources\Purchase;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'purchase_return_id' => $this->purchase_return_id,

            'purchase_order_item_id' => $this->purchase_order_item_id,

            'product_id' => $this->product_id,

            'product' => $this->whenLoaded(
                'product'
            ),

            'purchase_price' => $this->purchase_price,

            'quantity' => $this->quantity,

            'line_total' => $this->line_total,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}
