<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'sale_order_id' => $this->sale_order_id,

            'sale_order' => $this->whenLoaded(
                'saleOrder'
            ),

            'customer_id' => $this->customer_id,

            'customer' => $this->whenLoaded(
                'customer'
            ),

            'return_no' => $this->return_no,

            'return_date' => $this->return_date,

            'total_amount' => $this->total_amount,

            'status' => $this->status,

            'remarks' => $this->remarks,

            'items' => SaleReturnItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_by' => $this->created_by,

            'updated_by' => $this->updated_by,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

            'deleted_at' => $this->deleted_at,

        ];
    }
}
