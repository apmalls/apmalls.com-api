<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Helpers\NumberHelper;
use App\Jobs\SendDeliveryOtp;
use App\Models\Delivery\DeliveryAssignment;
use App\Models\Delivery\DeliveryConfirmation;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMode;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use App\Services\Sale\OrderNotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class DeliveryConfirmationService
{
    public function __construct(private OrderNotificationService $notifications) {}

    private const RELATIONS = [
        'deliveryReportedBy', 'customerConfirmedBy', 'disputedBy', 'resolvedBy',
    ];

    public function prepareDispatch(DeliveryAssignment $assignment): DeliveryConfirmation
    {
        // Caller holds assignment then order locks inside the dispatch transaction.
        $order = SaleOrder::query()->lockForUpdate()->findOrFail($assignment->sale_order_id);
        $confirmation = $this->pendingConfirmation($assignment, $order);
        $this->issueCode($assignment, $order, $confirmation);
        return $confirmation;
    }

    public function resendByCourier(User $user, int $assignmentId): DeliveryConfirmation
    {
        return DB::transaction(function () use ($user, $assignmentId) {
            $assignment = $this->ownedAssignment($user, $assignmentId);
            $order = SaleOrder::query()->lockForUpdate()->findOrFail($assignment->sale_order_id);
            $confirmation = $this->pendingConfirmation($assignment, $order);
            $this->assertCodeAllowed($assignment, $order, $confirmation);
            $this->issueCode($assignment, $order, $confirmation);
            return $confirmation->fresh(self::RELATIONS);
        });
    }

    private function ownedAssignment(User $user, int $id): DeliveryAssignment
    {
        $assignment = DeliveryAssignment::query()->with('deliveryBoy')->lockForUpdate()->findOrFail($id);
        if (! $user->is_active || ! $assignment->deliveryBoy?->is_active
            || $assignment->deliveryBoy->user_id !== $user->id) {
            throw new AuthorizationException('This delivery assignment does not belong to an active delivery profile.');
        }
        return $assignment;
    }

    private function pendingConfirmation(DeliveryAssignment $assignment, SaleOrder $order): DeliveryConfirmation
    {
        return DeliveryConfirmation::query()->where('delivery_assignment_id', $assignment->id)->lockForUpdate()->first()
            ?? DeliveryConfirmation::create(['delivery_assignment_id' => $assignment->id,
                'customer_id' => $order->customer_id, 'status' => DeliveryConfirmation::STATUS_PENDING_HANDOVER]);
    }

    private function assertCodeAllowed(DeliveryAssignment $assignment, SaleOrder $order, DeliveryConfirmation $confirmation): void
    {
        if ($assignment->status !== DeliveryAssignment::STATUS_OUT_FOR_DELIVERY
            || $order->status !== SaleOrder::STATUS_CONFIRMED
            || ! in_array($confirmation->status, [DeliveryConfirmation::STATUS_PENDING_HANDOVER, DeliveryConfirmation::STATUS_AWAITING_CUSTOMER], true)) {
            throw ValidationException::withMessages(['status' => ['This delivery is not eligible for a delivery code.']]);
        }
    }

    private function issueCode(DeliveryAssignment $assignment, SaleOrder $order, DeliveryConfirmation $confirmation): void
    {
        $this->assertCodeAllowed($assignment, $order, $confirmation);
        if ($confirmation->otp_issued_at?->copy()->addSeconds(60)->isFuture()) {
            throw new HttpException(429, 'Wait 60 seconds between delivery email requests.');
        }
        $inWindow = $confirmation->otp_send_window_at?->copy()->addHour()->isFuture();
        if ($inWindow && $confirmation->otp_send_count >= 5) {
            throw new HttpException(429, 'Five delivery emails have been requested this hour. Please contact a manager.');
        }
        $recipient = User::query()->lockForUpdate()->find($order->customer?->user_id);
        $version = (string) Str::uuid();
        $otp = (string) random_int(100000, 999999);
        $eligible = $recipient?->is_active && $recipient->email_verified_at && filter_var($recipient->email, FILTER_VALIDATE_EMAIL);
        $confirmation->update([
            'otp_hash' => $eligible ? Hash::make($otp) : null,
            'otp_expires_at' => $eligible ? now()->addHours(24) : null,
            'otp_attempts' => 0, 'otp_max_attempts' => 5, 'otp_issued_at' => now(),
            'otp_send_window_at' => $inWindow ? $confirmation->otp_send_window_at : now(),
            'otp_send_count' => $inWindow ? $confirmation->otp_send_count + 1 : 1,
            'otp_version' => $version, 'otp_email_status' => $eligible ? 'queued' : 'failed',
            'otp_email_sent_at' => null, 'otp_recipient_user_id' => $recipient?->id,
            'otp_recipient_email' => $eligible ? $recipient->email : null,
        ]);
        if ($eligible) {
            DB::afterCommit(function () use ($assignment, $version, $otp) {
                try {
                    SendDeliveryOtp::dispatch($assignment->id, $version, $otp);
                } catch (Throwable) {
                    DeliveryConfirmation::query()->where('delivery_assignment_id', $assignment->id)
                        ->where('otp_version', $version)->update(['otp_email_status' => 'failed']);
                }
            });
        }
    }

    public function reportHandover(
        User $user,
        int $assignmentId,
        bool $cashCollected,
        ?string $remarks = null
    ): DeliveryConfirmation {
        return DB::transaction(function () use ($user, $assignmentId, $cashCollected, $remarks) {
            $assignment = $this->ownedAssignment($user, $assignmentId);
            $order = SaleOrder::query()->lockForUpdate()->findOrFail($assignment->sale_order_id);
            if (strlen(trim($remarks ?? '')) < 5) {
                throw ValidationException::withMessages(['remarks' => ['Describe why manager assistance is needed.']]);
            }
            if ($assignment->status !== DeliveryAssignment::STATUS_OUT_FOR_DELIVERY) {
                throw ValidationException::withMessages([
                    'status' => ['Only an out-for-delivery assignment can report handover.'],
                ]);
            }

            $existing = DeliveryConfirmation::query()
                ->where('delivery_assignment_id', $assignment->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->status === DeliveryConfirmation::STATUS_DISPUTED) {
                    throw ValidationException::withMessages([
                        'status' => ['This handover is disputed and awaiting manager review.'],
                    ]);
                }
                if ($existing->status !== DeliveryConfirmation::STATUS_PENDING_HANDOVER) {
                    return $existing->load(self::RELATIONS);
                }
            }

            $due = round((float) $order->due_amount, 2);
            $confirmation = $existing ?? $this->pendingConfirmation($assignment, $order);
            $confirmation->update([
                'delivery_assignment_id' => $assignment->id,
                'customer_id' => $order->customer_id,
                'status' => DeliveryConfirmation::STATUS_AWAITING_CUSTOMER,
                'delivery_reported_by' => $user->id,
                'delivery_reported_at' => now(),
                'courier_remarks' => $remarks,
                'cash_collected_reported' => $due > 0 && $cashCollected,
                'cash_amount_reported' => $due > 0 && $cashCollected ? $due : 0,
                'otp_hash' => null, 'otp_version' => null, 'otp_expires_at' => null,
            ]);
            return $confirmation->load(self::RELATIONS);
        });
    }

    public function generateOtp(User $user, string $saleNo): array
    {
        return DB::transaction(function () use ($user, $saleNo) {
            [$order, $confirmation, $assignment] = $this->customerConfirmation($user, $saleNo);
            $this->issueCode($assignment, $order, $confirmation);

            return [
                'expires_at' => $confirmation->otp_expires_at,
                'order_no' => $order->sale_no,
                'email_status' => $confirmation->fresh()->otp_email_status,
                'resend_after' => 60,
            ];
        });
    }

    public function confirmByCustomer(User $user, string $saleNo, float $amountPaid): DeliveryConfirmation
    {
        abort(403, 'Delivery OTP verification by the courier is required. Contact a manager for exceptions.');
    }

    public function confirmByOtp(User $user, int $assignmentId, string $otp, bool $cashCollected = false, ?string $remarks = null): DeliveryConfirmation
    {
        $result = DB::transaction(function () use ($user, $assignmentId, $otp, $cashCollected, $remarks) {
            $assignment = $this->ownedAssignment($user, $assignmentId);
            $order = SaleOrder::query()->lockForUpdate()->findOrFail($assignment->sale_order_id);

            $confirmation = DeliveryConfirmation::query()
                ->with('customer')
                ->where('delivery_assignment_id', $assignment->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertCodeAllowed($assignment, $order, $confirmation);
            $recipient = User::query()->lockForUpdate()->find($order->customer?->user_id);
            if (! $recipient?->is_active || ! $recipient->email_verified_at
                || ($confirmation->otp_recipient_user_id && $recipient->id !== $confirmation->otp_recipient_user_id)
                || ($confirmation->otp_recipient_email && $recipient->email !== $confirmation->otp_recipient_email)) {
                throw ValidationException::withMessages(['otp' => ['Request a new delivery email for the verified customer account.']]);
            }

            if (! $confirmation->otp_hash || ! $confirmation->otp_expires_at?->isFuture()) {
                throw ValidationException::withMessages(['otp' => ['The delivery code is missing or expired.']]);
            }
            if ($confirmation->otp_attempts >= $confirmation->otp_max_attempts) {
                throw ValidationException::withMessages(['otp' => ['Maximum delivery code attempts exceeded.']]);
            }
            if (! Hash::check($otp, $confirmation->otp_hash)) {
                $confirmation->increment('otp_attempts');

                return ['error' => 'Invalid delivery code.'];
            }

            $customerUserId = $confirmation->customer?->user_id;
            $due = round((float) $order->due_amount, 2);
            if ($due > 0 && ! $cashCollected) {
                throw ValidationException::withMessages(['cash_collected' => ['Confirm that the current outstanding cash was collected.']]);
            }
            $confirmation->update([
                'status' => DeliveryConfirmation::STATUS_AWAITING_CUSTOMER,
                'delivery_reported_by' => $user->id, 'delivery_reported_at' => now(),
                'courier_remarks' => $remarks, 'cash_collected_reported' => $due > 0 && $cashCollected,
                'cash_amount_reported' => $due,
            ]);
            return [
                'confirmation' => $this->finalize(
                    $confirmation,
                    DeliveryConfirmation::METHOD_OTP,
                    $customerUserId,
                    $due
                ),
            ];
        });

        if (isset($result['error'])) {
            throw ValidationException::withMessages(['otp' => [$result['error']]]);
        }

        return $result['confirmation'];
    }

    public function dispute(User $user, string $saleNo, string $reason): DeliveryConfirmation
    {
        return DB::transaction(function () use ($user, $saleNo, $reason) {
            [, $confirmation] = $this->customerConfirmation($user, $saleNo);
            $confirmation->update([
                'status' => DeliveryConfirmation::STATUS_DISPUTED,
                'disputed_by' => $user->id,
                'disputed_at' => now(),
                'dispute_reason' => $reason,
                'otp_hash' => null,
                'otp_version' => null,
                'otp_expires_at' => null,
            ]);

            return $confirmation->fresh(self::RELATIONS);
        });
    }

    public function resolve(
        User $user,
        int $confirmationId,
        string $resolution,
        string $remarks
    ): DeliveryConfirmation {
        return DB::transaction(function () use ($user, $confirmationId, $resolution, $remarks) {
            $lookup = DeliveryConfirmation::findOrFail($confirmationId);
            $assignment = DeliveryAssignment::query()->lockForUpdate()->findOrFail($lookup->delivery_assignment_id);
            $order = SaleOrder::query()->lockForUpdate()->findOrFail($assignment->sale_order_id);
            $confirmation = DeliveryConfirmation::query()
                ->lockForUpdate()
                ->findOrFail($confirmationId);
            if (! in_array($confirmation->status, [
                DeliveryConfirmation::STATUS_AWAITING_CUSTOMER,
                DeliveryConfirmation::STATUS_DISPUTED,
            ], true)) {
                throw ValidationException::withMessages(['status' => ['This confirmation is already resolved.']]);
            }

            if ($resolution === 'confirm') {
                return $this->finalize(
                    $confirmation,
                    DeliveryConfirmation::METHOD_MANAGER,
                    null,
                    (float) $confirmation->cash_amount_reported,
                    $user->id,
                    $remarks
                );
            }

            if ($resolution !== 'reopen') {
                throw ValidationException::withMessages(['resolution' => ['Invalid resolution.']]);
            }

            $confirmation->update([
                'status' => DeliveryConfirmation::STATUS_RESOLVED_REOPENED,
                'resolved_by' => $user->id,
                'resolved_at' => now(),
                'resolution_remarks' => $remarks,
                'otp_hash' => null,
                'otp_version' => null,
                'otp_expires_at' => null,
            ]);
            $assignment->update([
                'status' => DeliveryAssignment::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);
            $order->forceFill([
                'status' => SaleOrder::STATUS_CONFIRMED,
                'delivery_status' => null,
                'delivered_at' => null,
            ])->save();

            return $confirmation->fresh(self::RELATIONS);
        });
    }

    private function finalize(
        DeliveryConfirmation $confirmation,
        string $method,
        ?int $customerUserId,
        float $confirmedAmount,
        ?int $resolvedBy = null,
        ?string $resolutionRemarks = null
    ): DeliveryConfirmation {
        $confirmation = DeliveryConfirmation::query()
            ->with('customer')
            ->lockForUpdate()
            ->findOrFail($confirmation->id);

        if (in_array($confirmation->status, [
            DeliveryConfirmation::STATUS_CONFIRMED,
            DeliveryConfirmation::STATUS_RESOLVED_CONFIRMED,
            DeliveryConfirmation::STATUS_LEGACY_COMPLETED,
        ], true)) {
            return $confirmation->load(self::RELATIONS);
        }
        if ($method !== DeliveryConfirmation::METHOD_MANAGER) {
            $this->assertAwaiting($confirmation);
        }

        $assignment = DeliveryAssignment::query()->lockForUpdate()->findOrFail($confirmation->delivery_assignment_id);
        $order = SaleOrder::query()->lockForUpdate()->findOrFail($assignment->sale_order_id);
        $due = round((float) $order->due_amount, 2);
        $reported = round((float) $confirmation->cash_amount_reported, 2);
        $confirmed = round($confirmedAmount, 2);

        if ($due > 0 && (
            ! $confirmation->cash_collected_reported ||
            $reported !== $due ||
            $confirmed !== $due
        )) {
            throw ValidationException::withMessages([
                'amount_paid' => ['Courier and customer payment confirmations must match the current order due.'],
            ]);
        }

        if ($due > 0) {
            $cashMode = PaymentMode::query()->where('code', 'CASH')->where('is_active', true)->first();
            if (! $cashMode) {
                throw ValidationException::withMessages(['payment_mode' => ['The active CASH payment mode is not configured.']]);
            }

            Payment::firstOrCreate(
                [
                    'paymentable_type' => SaleOrder::class,
                    'paymentable_id' => $order->id,
                    'reference_no' => "DELIVERY-{$assignment->id}",
                ],
                [
                    'payment_no' => NumberHelper::generate(Payment::class, 'payment_no', 'PAY'),
                    'payment_date' => today(),
                    'customer_id' => $order->customer_id,
                    'payment_mode_id' => $cashMode->id,
                    'amount' => $due,
                    'paid_amount' => $due,
                    'status' => Payment::STATUS_COMPLETED,
                    'remarks' => 'Customer-confirmed cash on delivery.',
                    'created_by' => $resolvedBy ?? $confirmation->delivery_reported_by,
                    'updated_by' => $resolvedBy ?? $confirmation->delivery_reported_by,
                ]
            );
        }

        $now = now();
        $confirmation->update([
            'status' => $method === DeliveryConfirmation::METHOD_MANAGER
                ? DeliveryConfirmation::STATUS_RESOLVED_CONFIRMED
                : DeliveryConfirmation::STATUS_CONFIRMED,
            'customer_confirmed_by' => $customerUserId,
            'customer_confirmed_at' => $now,
            'customer_confirmed_amount' => $confirmed,
            'payment_confirmed_at' => $due > 0 ? $now : null,
            'confirmation_method' => $method,
            'resolved_by' => $resolvedBy,
            'resolved_at' => $resolvedBy ? $now : null,
            'resolution_remarks' => $resolutionRemarks,
            'otp_hash' => null,
            'otp_version' => null,
            'otp_expires_at' => null,
        ]);
        $assignment->update([
            'status' => DeliveryAssignment::STATUS_DELIVERED,
            'delivered_at' => $now,
        ]);
        $order->forceFill([
            'status' => SaleOrder::STATUS_COMPLETED,
            'delivery_status' => DeliveryAssignment::STATUS_DELIVERED,
            'delivered_at' => $now,
            'paid_amount' => round((float) $order->paid_amount + $due, 2),
            'due_amount' => 0,
            'payment_status' => $due > 0 ? SaleOrder::PAYMENT_COMPLETED : $order->payment_status,
        ])->save();

        $this->notifications->deliveryCompleted($order, $confirmation->fresh());

        return $confirmation->fresh(self::RELATIONS);
    }

    private function customerConfirmation(
        User $user,
        string $saleNo
    ): array
    {
        $customer = $user->customer;
        if (! $customer) {
            throw new AuthorizationException('Customer profile not found.');
        }

        $orderQuery = SaleOrder::query()->where('customer_id', $customer->id)->where('sale_no', $saleNo);
        $orderLookup = $orderQuery->firstOrFail();
        $assignment = DeliveryAssignment::query()
            ->where('sale_order_id', $orderLookup->id)
            ->latest('id')
            ->lockForUpdate()
            ->firstOrFail();
        $order = SaleOrder::query()->lockForUpdate()->findOrFail($orderLookup->id);
        $confirmation = $this->pendingConfirmation($assignment, $order);
        $this->assertCodeAllowed($assignment, $order, $confirmation);

        return [$order, $confirmation, $assignment];
    }

    private function assertAwaiting(DeliveryConfirmation $confirmation): void
    {
        if ($confirmation->status !== DeliveryConfirmation::STATUS_AWAITING_CUSTOMER) {
            throw ValidationException::withMessages([
                'status' => ['This delivery is not awaiting customer confirmation.'],
            ]);
        }
    }

}
