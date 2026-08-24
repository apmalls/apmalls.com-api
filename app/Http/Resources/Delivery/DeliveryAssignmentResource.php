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
        return [

            'id' => $this->id,

            'sale_order' => [
                'id' => $this->saleOrder?->id,
                'order_no' => $this->saleOrder?->order_no,
                'delivery_status' => $this->saleOrder?->delivery_status,
            ],

            'delivery_boy' => [
                'id' => $this->deliveryBoy?->id,
                'employee_code' => $this->deliveryBoy?->employee_code,
                'phone' => $this->deliveryBoy?->phone,
            ],

            'assigned_by' => [
                'id' => $this->assignedBy?->id,
                'name' => trim(
                    ($this->assignedBy?->first_name ?? '') . ' ' .
                    ($this->assignedBy?->last_name ?? '')
                ),
            ],

            'status' => $this->status,

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
