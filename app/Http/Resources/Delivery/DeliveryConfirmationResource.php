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
            'otp_issued_at' => $this->otp_issued_at,
            'otp_email_status' => $this->otp_email_status,
            'otp_email_sent_at' => $this->otp_email_sent_at,
            'masked_recipient' => $this->maskedRecipient(),
            'resend_after' => $this->otp_issued_at
                ? max(0, (int) ceil(now()->diffInSeconds($this->otp_issued_at->copy()->addSeconds(60), false))) : 0,
            'resend_available_at' => $this->otp_send_count >= 5 && $this->otp_send_window_at?->copy()->addHour()->isFuture()
                ? $this->otp_send_window_at->copy()->addHour() : $this->otp_issued_at?->copy()->addSeconds(60),
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

    private function maskedRecipient(): ?string
    {
        if (! $this->otp_recipient_email || ! str_contains($this->otp_recipient_email, '@')) {
            return null;
        }
        [$local, $domain] = explode('@', $this->otp_recipient_email, 2);
        return substr($local, 0, min(2, max(1, strlen($local) - 1))) . '***@' . $domain;
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
