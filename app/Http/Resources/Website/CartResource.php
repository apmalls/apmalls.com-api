<?php

declare(strict_types=1);

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'cart_no' => $this->cart_no,

            'customer_id' => $this->customer_id,

            'coupon' => $this->coupon
                ? [

                    'id' => $this->coupon->id,

                    'name' => $this->coupon->name,

                    'code' => $this->coupon->code,

                    'discount_type' => $this->coupon->discount_type,

                    'discount_value' => $this->coupon->discount_value,

                ]
                : null,

            'subtotal' => (float) $this->subtotal,

            'discount_amount' => (float) $this->discount_amount,

            'tax_amount' => (float) $this->tax_amount,

            'shipping_charge' => (float) $this->shipping_charge,

            'grand_total' => (float) $this->grand_total,

            'total_items' => $this->items->count(),

            'items' => CartItemResource::collection(
                $this->items
            ),

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}
