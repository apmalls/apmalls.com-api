<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\DeliveryOtpMail;
use App\Models\Delivery\DeliveryAssignment;
use App\Models\Delivery\DeliveryConfirmation;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendDeliveryOtp implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $assignmentId, public string $version, private string $otp) {}

    public function backoff(): array { return [60, 120]; }

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                // Match completion/cancellation lock order, and never send stale codes.
                $assignment = DeliveryAssignment::query()->lockForUpdate()->find($this->assignmentId);
                if (! $assignment || $assignment->status !== DeliveryAssignment::STATUS_OUT_FOR_DELIVERY) {
                    return;
                }
                $order = SaleOrder::query()->lockForUpdate()->findOrFail($assignment->sale_order_id);
                $confirmation = DeliveryConfirmation::query()->where('delivery_assignment_id', $assignment->id)->lockForUpdate()->first();
                if (! $confirmation || $confirmation->otp_version !== $this->version
                    || ! $confirmation->otp_hash || ! $confirmation->otp_expires_at?->isFuture()
                    || ! in_array($confirmation->status, [DeliveryConfirmation::STATUS_PENDING_HANDOVER, DeliveryConfirmation::STATUS_AWAITING_CUSTOMER], true)
                    || $confirmation->otp_email_status === 'sent') {
                    return;
                }
                $recipient = User::query()->lockForUpdate()->find($confirmation->otp_recipient_user_id);
                if (! $recipient?->is_active || ! $recipient->email_verified_at
                    || $recipient->email !== $confirmation->otp_recipient_email
                    || $order->customer?->user_id !== $recipient->id) {
                    $confirmation->update(['otp_email_status' => 'failed', 'otp_hash' => null, 'otp_version' => null]);
                    return;
                }
                $assignment->load('deliveryBoy.user');
                Mail::to($recipient->email)->send(new DeliveryOtpMail($order->sale_no, $this->otp,
                    $confirmation->otp_expires_at->toIso8601String(), (float) $order->due_amount,
                    $assignment->deliveryBoy?->user?->full_name, $assignment->deliveryBoy?->phone));
                $confirmation->update(['otp_email_status' => 'sent', 'otp_email_sent_at' => now()]);
            });
        } catch (Throwable) {
            // Sanitize transport failures so worker logs cannot contain message contents.
            throw new RuntimeException('Delivery email could not be sent to the mail server.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        DeliveryConfirmation::query()->where('delivery_assignment_id', $this->assignmentId)
            ->where('otp_version', $this->version)->where('otp_email_status', 'queued')->update(['otp_email_status' => 'failed']);
    }
}
