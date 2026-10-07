<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\OrderNotificationMail;
use App\Models\Sale\OrderEmailDelivery;
use App\Services\Sale\OrderNotificationService;
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

class SendOrderNotification implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $deliveryId, public string $version) {}

    public function backoff(): array { return [60, 120]; }

    public function handle(OrderNotificationService $service): void
    {
        $failed = DB::transaction(function () use ($service) {
            $delivery = OrderEmailDelivery::query()->lockForUpdate()->find($this->deliveryId);
            if (! $delivery || $delivery->version !== $this->version || $delivery->status !== 'queued') {
                return false;
            }
            if ($delivery->audience === 'customer' && $service->customerRecipient($delivery) !== $delivery->recipient) {
                $delivery->update(['status' => 'blocked', 'error_code' => 'recipient_unavailable']);
                return false;
            }
            $delivery->update(['attempts' => $delivery->attempts + 1, 'last_attempt_at' => now()]);
            try {
                Mail::to($delivery->recipient)->send(new OrderNotificationMail($delivery->event, $delivery->audience, $delivery->snapshot));
            } catch (Throwable) {
                // Commit attempt metadata but never put message contents or addresses in worker errors.
                $delivery->update(['error_code' => 'smtp_failed']);
                return true;
            }
            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error_code' => null]);
            return false;
        });
        if ($failed) {
            throw new RuntimeException('Order notification could not be sent to the mail server.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        OrderEmailDelivery::query()->whereKey($this->deliveryId)->where('version', $this->version)
            ->where('status', 'queued')->update(['status' => 'failed', 'error_code' => 'smtp_failed']);
    }
}
