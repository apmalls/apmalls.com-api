<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'customer_id' => $this->customer_id,

            'customer' => $this->whenLoaded('customer'),

            'sale_no' => $this->sale_no,

            'invoice_no' => $this->invoice_no,

            'sale_date' => $this->sale_date,

            'invoice_date' => $this->invoice_date,

            'billing_address_id' => $this->billing_address_id,

            'billing_address' => $this->whenLoaded('billingAddress'),

            'shipping_address_id' => $this->shipping_address_id,

            'shipping_address' => $this->whenLoaded('shippingAddress'),

            'sub_total' => $this->sub_total,

            'discount_amount' => $this->discount_amount,

            'tax_amount' => $this->tax_amount,

            'shipping_amount' => $this->shipping_amount,

            'other_amount' => $this->other_amount,

            'round_off' => $this->round_off,

            'grand_total' => $this->grand_total,

            'paid_amount' => $this->paid_amount,

            'due_amount' => $this->due_amount,

            'refund_amount' => $this->refund_amount,

            'payment_status' => $this->payment_status,

            'status' => $this->status,

            'remarks' => $this->remarks,

            'total_items' => $this->total_items,

            'items' => SaleOrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            'returns' => SaleReturnResource::collection(
                $this->whenLoaded('saleReturns')
            ),

            'creator' => $this->whenLoaded('creator'),

            'updater' => $this->whenLoaded('updater'),

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}
