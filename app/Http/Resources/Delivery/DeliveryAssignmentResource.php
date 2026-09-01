<?php

declare(strict_types=1);

namespace App\Http\Resources\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Delivery\DeliveryAssignment
 */
class DeliveryAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->saleOrder;
        $address = $order?->shippingAddress;
        $customer = $order?->customer;
        $confirmation = $this->confirmation;
        $nextActions = match (true) {
            $confirmation && in_array($confirmation->status, ['awaiting_customer', 'disputed'], true) => [],
            $this->status === 'assigned' => ['accept', 'reject'],
            $this->status === 'accepted' => ['pickup'],
            $this->status === 'picked' => ['out_for_delivery'],
            $this->status === 'out_for_delivery' => ['delivered'],
            default => [],
        };

        return [

            'id' => $this->id,

            'sale_order' => [
                'id' => $order?->id,
                'order_no' => $order?->sale_no ?? $order?->invoice_no,
                'invoice_no' => $order?->invoice_no,
                'status' => $order?->status,
                'delivery_status' => $order?->delivery_status,
                'payment_status' => $order?->payment_status,
                'grand_total' => $order?->grand_total,
                'paid_amount' => $order?->paid_amount,
                'due_amount' => $order?->due_amount,
                'total_items' => $order?->total_items,
                'remarks' => $order?->remarks,
                'customer' => [
                    'name' => trim(($customer?->first_name ?? '') . ' ' . ($customer?->last_name ?? '')),
                    'mobile' => $customer?->mobile,
                    'email' => $customer?->email,
                ],
                'shipping_address' => $address ? [
                    'contact_person' => $address->contact_person,
                    'mobile' => $address->mobile,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'country' => $address->country,
                    'postal_code' => $address->postal_code,
                ] : null,
                'items' => $order?->relationLoaded('items')
                    ? $order->items->map(fn ($item) => [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'name' => $item->product?->name,
                        'sku' => $item->product?->sku,
                        'quantity' => $item->quantity,
                    ])->values()
                    : [],
            ],

            'delivery_boy' => [
                'id' => $this->deliveryBoy?->id,
                'employee_code' => $this->deliveryBoy?->employee_code,
                'phone' => $this->deliveryBoy?->phone,
                'user' => $this->deliveryBoy?->user ? [
                    'id' => $this->deliveryBoy->user->id,
                    'first_name' => $this->deliveryBoy->user->first_name,
                    'last_name' => $this->deliveryBoy->user->last_name,
                    'full_name' => $this->deliveryBoy->user->full_name,
                    'email' => $this->deliveryBoy->user->email,
                    'mobile' => $this->deliveryBoy->user->mobile,
                ] : null,
            ],

            'assigned_by' => [
                'id' => $this->assignedBy?->id,
                'name' => trim(
                    ($this->assignedBy?->first_name ?? '') . ' ' .
                    ($this->assignedBy?->last_name ?? '')
                ),
            ],

            'status' => $this->status,

            'delivery_confirmation' => new DeliveryConfirmationResource(
                $this->whenLoaded('confirmation')
            ),

            'next_actions' => $nextActions,

            'remarks' => $this->remarks,

            'assigned_at' => $this->assigned_at,

            'accepted_at' => $this->accepted_at,

            'rejected_at' => $this->rejected_at,

            'picked_at' => $this->picked_at,

            'out_for_delivery_at' => $this->out_for_delivery_at,

            'delivered_at' => $this->delivered_at,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}
