<?php

namespace App\Http\Resources\Purchase;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,

            'purchase_no' => $this->purchase_no,

            'invoice_no' => $this->invoice_no,

            'purchase_date' => optional($this->purchase_date)->format('Y-m-d'),

            'invoice_date' => optional($this->invoice_date)->format('Y-m-d'),

            /*
            |--------------------------------------------------------------------------
            | Relations
            |--------------------------------------------------------------------------
            */

            'supplier' => $this->whenLoaded('supplier'),

            'warehouse' => $this->whenLoaded('warehouse'),

            /*
            |--------------------------------------------------------------------------
            | Amounts
            |--------------------------------------------------------------------------
            */

            'sub_total' => (float) $this->sub_total,

            'discount_amount' => (float) $this->discount_amount,

            'tax_amount' => (float) $this->tax_amount,

            'shipping_amount' => (float) $this->shipping_amount,

            'other_amount' => (float) $this->other_amount,

            'round_off' => (float) $this->round_off,

            'grand_total' => (float) $this->grand_total,

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'paid_amount' => (float) $this->paid_amount,

            'due_amount' => (float) $this->due_amount,

            'refund_amount' => (float) $this->refund_amount,

            'payment_status' => $this->payment_status,

            /*
            |--------------------------------------------------------------------------
            | Purchase Status
            |--------------------------------------------------------------------------
            */

            'status' => $this->status,

            'remarks' => $this->remarks,

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => PurchaseOrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            /*
            |--------------------------------------------------------------------------
            | Returns
            |--------------------------------------------------------------------------
            */

            'purchase_returns' => PurchaseReturnResource::collection(
                $this->whenLoaded('purchaseReturns')
            ),

            /*
            |--------------------------------------------------------------------------
            | Payments
            |--------------------------------------------------------------------------
            */

            'payments' => $this->whenLoaded('payments'),

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'created_by' => $this->whenLoaded('creator'),

            'updated_by' => $this->whenLoaded('updater'),

            'created_at' => optional($this->created_at)
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->format('Y-m-d H:i:s'),

            'deleted_at' => optional($this->deleted_at)
                ->format('Y-m-d H:i:s'),

        ];
    }
}
