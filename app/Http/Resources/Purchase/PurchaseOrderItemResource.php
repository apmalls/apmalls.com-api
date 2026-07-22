<?php

namespace App\Http\Resources\Purchase;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
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

            /*
            |--------------------------------------------------------------------------
            | Relations
            |--------------------------------------------------------------------------
            */

            'purchase_order_id' => $this->purchase_order_id,

            'product_id' => $this->product_id,

            'unit_id' => $this->unit_id,

            'product' => $this->whenLoaded('product'),

            'unit' => $this->whenLoaded('unit'),

            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'quantity' => (float) $this->quantity,

            'received_quantity' => (float) $this->received_quantity,

            'pending_quantity' => (float) $this->pending_quantity,

            'free_quantity' => (float) $this->free_quantity,

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            'unit_cost' => (float) $this->unit_cost,

            'tax_percent' => (float) $this->tax_percent,

            'tax_amount' => (float) $this->tax_amount,

            'discount_percent' => (float) $this->discount_percent,

            'discount_amount' => (float) $this->discount_amount,

            'line_total' => (float) $this->line_total,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_pending' => $this->isPending(),

            'is_partially_received' => $this->isPartiallyReceived(),

            'is_fully_received' => $this->isFullyReceived(),

            'received_percentage' => $this->received_percentage,

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'created_at' => optional($this->created_at)
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->format('Y-m-d H:i:s'),

        ];
    }
}
