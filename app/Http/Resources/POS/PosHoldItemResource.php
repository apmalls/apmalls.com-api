<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosHoldItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'product_id' => $this->product_id,

            'product' => new ProductResource(
                $this->whenLoaded('product')
            ),

            'quantity' => $this->quantity,

            'price' => $this->price,

            'discount' => $this->discount,

            'tax' => $this->tax,

            'total' => $this->total,

        ];
    }
}
