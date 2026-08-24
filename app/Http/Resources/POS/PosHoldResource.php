<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosHoldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'hold_no' => $this->hold_no,

            'customer_id' => $this->customer_id,

            'customer' => new CustomerResource(
                $this->whenLoaded('customer')
            ),

            'sub_total' => $this->sub_total,

            'discount' => $this->discount,

            'tax' => $this->tax,

            'grand_total' => $this->grand_total,

            'status' => $this->status,

            'remarks' => $this->remarks,

            'items' => PosHoldItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at,

        ];
    }
}
