<?php

namespace App\Http\Resources\Purchase;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'purchase_order_id' => $this->purchase_order_id,

            'purchase_order' => $this->whenLoaded(
                'purchaseOrder'
            ),

            'supplier_id' => $this->supplier_id,

            'supplier' => $this->whenLoaded(
                'supplier'
            ),

            'return_no' => $this->return_no,

            'return_date' => $this->return_date,

            'total_amount' => $this->total_amount,

            'status' => $this->status,

            'remarks' => $this->remarks,

            'items' => PurchaseReturnItemResource::collection(
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
