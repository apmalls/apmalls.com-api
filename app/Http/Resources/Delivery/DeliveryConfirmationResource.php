<?php

declare(strict_types=1);

namespace App\Http\Resources\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryConfirmationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignment = $this->whenLoaded('assignment');
        $order = isset($assignment->id) && $assignment->relationLoaded('saleOrder')
            ? $assignment->saleOrder
            : null;

        return [
            'id' => $this->id,
            'delivery_assignment_id' => $this->delivery_assignment_id,
            'order' => $order ? [
                'id' => $order->id,
                'sale_no' => $order->sale_no,
                'status' => $order->status,
                'delivery_status' => $order->delivery_status,
                'payment_status' => $order->payment_status,
                'due_amount' => $order->due_amount,
            ] : null,
            'status' => $this->status,
            'delivery_reported_at' => $this->delivery_reported_at,
            'delivery_reported_by' => $this->userSummary($this->whenLoaded('deliveryReportedBy')),
            'courier_remarks' => $this->courier_remarks,
            'cash_collected_reported' => $this->cash_collected_reported,
            'cash_amount_reported' => $this->cash_amount_reported,
            'customer_confirmed_at' => $this->customer_confirmed_at,
            'customer_confirmed_by' => $this->userSummary($this->whenLoaded('customerConfirmedBy')),
            'customer_confirmed_amount' => $this->customer_confirmed_amount,
            'payment_confirmed_at' => $this->payment_confirmed_at,
            'confirmation_method' => $this->confirmation_method,
            'otp_expires_at' => $this->otp_expires_at,
            'otp_attempts' => $this->otp_attempts,
            'otp_max_attempts' => $this->otp_max_attempts,
            'disputed_at' => $this->disputed_at,
            'disputed_by' => $this->userSummary($this->whenLoaded('disputedBy')),
            'dispute_reason' => $this->dispute_reason,
            'resolved_at' => $this->resolved_at,
            'resolved_by' => $this->userSummary($this->whenLoaded('resolvedBy')),
            'resolution_remarks' => $this->resolution_remarks,
        ];
    }

    private function userSummary($user): ?array
    {
        if (! $user || ! isset($user->id)) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
        ];
    }
}
