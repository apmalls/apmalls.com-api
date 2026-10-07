<?php

declare(strict_types=1);

namespace App\Services\Sale;

use App\Jobs\SendOrderNotification;
use App\Models\Delivery\DeliveryConfirmation;
use App\Models\Sale\OrderEmailDelivery;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class OrderNotificationService
{
    public function orderPlaced(SaleOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order = SaleOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (in_array($order->status, [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_COMPLETED], true)) {
                $this->record($order, 'order_placed', 'admin', $this->snapshot($order));
            }
        });
    }

    public function deliveryCompleted(SaleOrder $order, DeliveryConfirmation $confirmation): void
    {
        DB::transaction(function () use ($order, $confirmation) {
            $order = SaleOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->status !== SaleOrder::STATUS_COMPLETED || ! in_array($confirmation->status,
                [DeliveryConfirmation::STATUS_CONFIRMED, DeliveryConfirmation::STATUS_RESOLVED_CONFIRMED], true)) {
                return;
            }
            $snapshot = $this->snapshot($order);
            $confirmation->load('assignment.deliveryBoy.user');
            $courier = $confirmation->assignment?->deliveryBoy;
            $snapshot['courier_name'] = $courier?->user?->full_name;
            $snapshot['courier_phone'] = $courier?->phone;
            $snapshot['confirmation_method'] = $confirmation->confirmation_method === DeliveryConfirmation::METHOD_MANAGER
                ? 'Audited manager confirmation' : 'Delivery OTP';
            $snapshot['cod_collected'] = $confirmation->cash_collected_reported ? (float) $confirmation->cash_amount_reported : 0;
            $this->record($order, 'delivery_completed', 'admin', $snapshot);
            $this->record($order, 'delivery_completed', 'customer', $snapshot);
        });
    }

    private function snapshot(SaleOrder $order): array
    {
        $order->load(['customer.user', 'items.product', 'items.unit', 'payments.paymentMode']);
        $customer = $order->customer;
        return [
            'order_number' => $order->sale_no,
            'source' => match ($order->order_source) { 'online' => 'Online', 'pos' => 'POS', default => 'Manual' },
            'occurred_at' => now()->setTimezone(config('app.business_timezone', 'Asia/Kolkata'))->format('d M Y, h:i A').' IST',
            'customer_name' => trim(($customer?->first_name ?? '').' '.($customer?->last_name ?? '')) ?: 'Walk-in customer',
            'customer_email' => $customer?->user?->email ?? $customer?->email,
            'customer_mobile' => $customer?->mobile,
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->product?->name ?? 'Product', 'quantity' => $item->quantity,
                'unit' => $item->unit?->name, 'price' => (float) $item->selling_price,
                'discount' => (float) $item->discount_amount, 'tax' => (float) $item->tax_amount,
                'total' => (float) $item->line_total,
            ])->all(),
            'sub_total' => (float) $order->sub_total, 'discount' => (float) $order->discount_amount,
            'tax' => (float) $order->tax_amount, 'shipping' => (float) $order->shipping_amount,
            'other_amount' => (float) $order->other_amount, 'round_off' => (float) $order->round_off,
            'total' => (float) $order->grand_total, 'paid' => (float) $order->paid_amount,
            'due' => (float) $order->due_amount, 'payment_status' => $order->payment_status,
            'payment_methods' => $order->payments->filter(fn ($payment) => $payment->status === 'completed')
                ->map(fn ($payment) => $payment->paymentMode?->name)->filter()->unique()->values()->all(),
        ];
    }

    private function record(SaleOrder $order, string $event, string $audience, array $snapshot): void
    {
        // Lock the order to serialize identical event requests, including nested POS transactions.
        SaleOrder::query()->lockForUpdate()->findOrFail($order->id);
        $delivery = OrderEmailDelivery::firstOrCreate(
            ['sale_order_id' => $order->id, 'event' => $event, 'audience' => $audience],
            ['snapshot' => $snapshot, 'recipient_user_id' => $audience === 'customer' ? $order->customer?->user_id : null,
                'status' => 'pending', 'version' => (string) Str::uuid()]
        );
        if ($delivery->wasRecentlyCreated) {
            $this->prepare($delivery);
        }
    }

    public function retry(int $id, bool $includeQueued = false): bool
    {
        return DB::transaction(function () use ($id, $includeQueued) {
            $delivery = OrderEmailDelivery::query()->lockForUpdate()->findOrFail($id);
            if (! in_array($delivery->status, $includeQueued ? ['pending', 'failed', 'blocked', 'queued'] : ['pending', 'failed', 'blocked'], true)) {
                return false;
            }
            $this->prepare($delivery);
            return $delivery->status === 'queued';
        });
    }

    private function prepare(OrderEmailDelivery $delivery): void
    {
        $recipient = $delivery->audience === 'admin' ? trim((string) config('mail.order_notification_email'))
            : $this->customerRecipient($delivery);
        $valid = filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false;
        $delivery->update(['recipient' => $valid ? $recipient : null,
            'status' => $valid ? 'queued' : 'blocked', 'version' => (string) Str::uuid(),
            'error_code' => $valid ? null : 'recipient_unavailable']);
        if (! $valid) {
            return;
        }
        $id = $delivery->id;
        $version = $delivery->version;
        DB::afterCommit(function () use ($id, $version) {
            try {
                SendOrderNotification::dispatch($id, $version);
            } catch (Throwable) {
                try {
                    OrderEmailDelivery::query()->whereKey($id)->where('version', $version)
                        ->where('status', 'queued')->update(['status' => 'failed', 'error_code' => 'queue_dispatch_failed']);
                } catch (Throwable) {
                    Log::warning('Order notification queue dispatch failed; ledger status could not be updated.');
                }
            }
        });
    }

    public function customerRecipient(OrderEmailDelivery $delivery): ?string
    {
        $user = User::query()->lockForUpdate()->find($delivery->recipient_user_id);
        $order = SaleOrder::withTrashed()->with('customer')->find($delivery->sale_order_id);
        return $user?->is_active && $user->email_verified_at && $order?->customer?->user_id === $user->id
            ? $user->email : null;
    }
}
