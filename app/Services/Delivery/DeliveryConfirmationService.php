<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Helpers\NumberHelper;
use App\Models\Delivery\DeliveryAssignment;
use App\Models\Delivery\DeliveryConfirmation;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMode;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DeliveryConfirmationService
{
    private const RELATIONS = [
        'deliveryReportedBy', 'customerConfirmedBy', 'disputedBy', 'resolvedBy',
    ];

    public function reportHandover(
        User $user,
        int $assignmentId,
        bool $cashCollected,
        ?string $remarks = null
    ): DeliveryConfirmation {
        return DB::transaction(function () use ($user, $assignmentId, $cashCollected, $remarks) {
            $assignment = DeliveryAssignment::query()
                ->with('deliveryBoy')
                ->lockForUpdate()
                ->findOrFail($assignmentId);

            if ($assignment->deliveryBoy?->user_id !== $user->id) {
                throw new AuthorizationException('This delivery assignment does not belong to you.');
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
                return $existing->load(self::RELATIONS);
            }

            $order = SaleOrder::query()->lockForUpdate()->findOrFail($assignment->sale_order_id);
            $due = round((float) $order->due_amount, 2);
            if ($due > 0 && ! $cashCollected) {
                throw ValidationException::withMessages([
                    'cash_collected' => ['Confirm that the outstanding cash was collected.'],
                ]);
            }

            return DeliveryConfirmation::create([
                'delivery_assignment_id' => $assignment->id,
                'customer_id' => $order->customer_id,
                'status' => DeliveryConfirmation::STATUS_AWAITING_CUSTOMER,
                'delivery_reported_by' => $user->id,
                'delivery_reported_at' => now(),
                'courier_remarks' => $remarks,
                'cash_collected_reported' => $due > 0 && $cashCollected,
                'cash_amount_reported' => $due > 0 && $cashCollected ? $due : 0,
            ])->load(self::RELATIONS);
        });
    }

    public function generateOtp(User $user, string $saleNo): array
    {
        return DB::transaction(function () use ($user, $saleNo) {
            [$order, $confirmation] = $this->customerConfirmation($user, $saleNo, true);
            $otp = (string) random_int(100000, 999999);

            $confirmation->update([
                'otp_hash' => Hash::make($otp),
                'otp_expires_at' => now()->addMinutes(5),
                'otp_attempts' => 0,
                'otp_max_attempts' => 5,
            ]);

            return [
                'otp' => $otp,
                'expires_at' => $confirmation->otp_expires_at,
                'order_no' => $order->sale_no,
            ];
        });
    }

    public function confirmByCustomer(User $user, string $saleNo, float $amountPaid): DeliveryConfirmation
    {
        return DB::transaction(function () use ($user, $saleNo, $amountPaid) {
            [$order, $confirmation] = $this->customerConfirmation($user, $saleNo, true, false);
            if ($this->isFinal($confirmation)) {
                return $confirmation->load(self::RELATIONS);
            }
            $this->assertAwaiting($confirmation);
            $due = round((float) $order->due_amount, 2);
            if (round($amountPaid, 2) !== $due) {
                throw ValidationException::withMessages([
                    'amount_paid' => ['Confirm the exact outstanding amount or raise a dispute.'],
                ]);
            }

            return $this->finalize(
                $confirmation,
                DeliveryConfirmation::METHOD_APP,
                $user->id,
                $due
            );
        });
    }

    public function confirmByOtp(User $user, int $assignmentId, string $otp): DeliveryConfirmation
    {
        $result = DB::transaction(function () use ($user, $assignmentId, $otp) {
            $assignment = DeliveryAssignment::query()
                ->with('deliveryBoy')
                ->lockForUpdate()
                ->findOrFail($assignmentId);
            if ($assignment->deliveryBoy?->user_id !== $user->id) {
                throw new AuthorizationException('This delivery assignment does not belong to you.');
            }

            $confirmation = DeliveryConfirmation::query()
                ->with('customer')
                ->where('delivery_assignment_id', $assignment->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertAwaiting($confirmation);

            if (! $confirmation->otp_hash || ! $confirmation->otp_expires_at || $confirmation->otp_expires_at->isPast()) {
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
            return [
                'confirmation' => $this->finalize(
                    $confirmation,
                    DeliveryConfirmation::METHOD_OTP,
                    $customerUserId,
                    (float) $confirmation->cash_amount_reported
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
            [, $confirmation] = $this->customerConfirmation($user, $saleNo, true);
            $confirmation->update([
                'status' => DeliveryConfirmation::STATUS_DISPUTED,
                'disputed_by' => $user->id,
                'disputed_at' => now(),
                'dispute_reason' => $reason,
                'otp_hash' => null,
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

            $assignment = DeliveryAssignment::query()->lockForUpdate()->findOrFail($confirmation->delivery_assignment_id);
            $order = SaleOrder::query()->lockForUpdate()->findOrFail($assignment->sale_order_id);

            $confirmation->update([
                'status' => DeliveryConfirmation::STATUS_RESOLVED_REOPENED,
                'resolved_by' => $user->id,
                'resolved_at' => now(),
                'resolution_remarks' => $remarks,
                'otp_hash' => null,
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

        return $confirmation->fresh(self::RELATIONS);
    }

    private function customerConfirmation(
        User $user,
        string $saleNo,
        bool $lock,
        bool $requireAwaiting = true
    ): array
    {
        $customer = $user->customer;
        if (! $customer) {
            throw new AuthorizationException('Customer profile not found.');
        }

        $orderQuery = SaleOrder::query()->where('customer_id', $customer->id)->where('sale_no', $saleNo);
        $order = $lock ? $orderQuery->lockForUpdate()->firstOrFail() : $orderQuery->firstOrFail();
        $assignment = DeliveryAssignment::query()
            ->where('sale_order_id', $order->id)
            ->latest('id')
            ->lockForUpdate()
            ->firstOrFail();
        $confirmation = DeliveryConfirmation::query()
            ->where('delivery_assignment_id', $assignment->id)
            ->lockForUpdate()
            ->firstOrFail();
        if ($requireAwaiting) {
            $this->assertAwaiting($confirmation);
        }

        return [$order, $confirmation];
    }

    private function assertAwaiting(DeliveryConfirmation $confirmation): void
    {
        if ($confirmation->status !== DeliveryConfirmation::STATUS_AWAITING_CUSTOMER) {
            throw ValidationException::withMessages([
                'status' => ['This delivery is not awaiting customer confirmation.'],
            ]);
        }
    }

    private function isFinal(DeliveryConfirmation $confirmation): bool
    {
        return in_array($confirmation->status, [
            DeliveryConfirmation::STATUS_CONFIRMED,
            DeliveryConfirmation::STATUS_RESOLVED_CONFIRMED,
            DeliveryConfirmation::STATUS_LEGACY_COMPLETED,
        ], true);
    }
}
