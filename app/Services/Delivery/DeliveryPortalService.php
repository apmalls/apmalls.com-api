<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Models\Delivery\DeliveryAssignment;
use App\Models\Delivery\DeliveryBoy;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryPortalService
{
    public function __construct(private readonly DeliveryConfirmationService $confirmationService)
    {
    }

    private const ACTIVE_STATUSES = [
        DeliveryAssignment::STATUS_ASSIGNED,
        DeliveryAssignment::STATUS_ACCEPTED,
        DeliveryAssignment::STATUS_PICKED,
        DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
    ];

    private const TRANSITIONS = [
        'accept' => [DeliveryAssignment::STATUS_ASSIGNED, DeliveryAssignment::STATUS_ACCEPTED, 'accepted_at'],
        'reject' => [DeliveryAssignment::STATUS_ASSIGNED, DeliveryAssignment::STATUS_REJECTED, 'rejected_at'],
        'pickup' => [DeliveryAssignment::STATUS_ACCEPTED, DeliveryAssignment::STATUS_PICKED, 'picked_at'],
        'out_for_delivery' => [DeliveryAssignment::STATUS_PICKED, DeliveryAssignment::STATUS_OUT_FOR_DELIVERY, 'out_for_delivery_at'],
    ];

    public function profile(User $user): ?DeliveryBoy
    {
        return $user->deliveryBoy()->first();
    }

    public function assignments(User $user, array $filters = []): LengthAwarePaginator
    {
        $profile = $this->requireProfile($user);

        return DeliveryAssignment::query()
            ->with($this->relations())
            ->where('delivery_boy_id', $profile->id)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(min(max((int) ($filters['per_page'] ?? 15), 1), 100));
    }

    public function assignment(User $user, int $id): DeliveryAssignment
    {
        $profile = $this->requireProfile($user);
        $assignment = DeliveryAssignment::with($this->relations())->findOrFail($id);

        if ($assignment->delivery_boy_id !== $profile->id) {
            throw new AuthorizationException('This delivery assignment does not belong to you.');
        }

        return $assignment;
    }

    public function updateAvailability(User $user, bool $available): DeliveryBoy
    {
        $profile = $this->requireProfile($user);

        if (! $profile->is_active) {
            throw ValidationException::withMessages(['is_available' => ['An inactive delivery profile cannot be made available.']]);
        }

        $profile->update(['is_available' => $available]);

        return $profile->fresh('user');
    }

    public function transition(User $user, int $id, string $action, ?string $remarks = null): DeliveryAssignment
    {
        if (! isset(self::TRANSITIONS[$action])) {
            throw ValidationException::withMessages(['status' => ['Unsupported delivery action.']]);
        }

        if ($action === 'reject' && blank($remarks)) {
            throw ValidationException::withMessages(['remarks' => ['Remarks are required when rejecting an assignment.']]);
        }

        return DB::transaction(function () use ($user, $id, $action, $remarks) {
            $profile = $this->requireProfile($user);
            $assignment = DeliveryAssignment::query()->lockForUpdate()->findOrFail($id);

            $this->assertOwnership($assignment, $profile);
            [$expected, $next, $timestamp] = self::TRANSITIONS[$action];

            if ($assignment->status !== $expected) {
                throw ValidationException::withMessages(['status' => ["This assignment cannot be {$action} from its current status."]]);
            }

            $assignment->update(array_filter([
                'status' => $next,
                $timestamp => now(),
                'remarks' => $remarks,
            ], fn ($value) => $value !== null));

            $order = SaleOrder::query()->lockForUpdate()->findOrFail($assignment->sale_order_id);
            $order->forceFill(['delivery_status' => $action === 'reject' ? null : $next])->save();

            return $assignment->fresh($this->relations());
        });
    }

    public function delivered(User $user, int $id, bool $cashCollected, ?string $remarks = null): DeliveryAssignment
    {
        $this->confirmationService->reportHandover($user, $id, $cashCollected, $remarks);

        return $this->assignment($user, $id);
    }

    public function confirmOtp(User $user, int $id, string $otp): DeliveryAssignment
    {
        $this->confirmationService->confirmByOtp($user, $id, $otp);

        return $this->assignment($user, $id);
    }

    private function requireProfile(User $user): DeliveryBoy
    {
        $profile = $this->profile($user);
        if (! $profile) {
            throw ValidationException::withMessages(['profile' => ['Delivery profile setup is incomplete. Ask an administrator to complete it.']]);
        }
        if (! $profile->is_active || ! $user->is_active) {
            throw new AuthorizationException('This delivery profile is inactive.');
        }
        return $profile;
    }

    private function assertOwnership(DeliveryAssignment $assignment, DeliveryBoy $profile): void
    {
        if ($assignment->delivery_boy_id !== $profile->id) {
            throw new AuthorizationException('This delivery assignment does not belong to you.');
        }
    }

    private function relations(): array
    {
        return [
            'saleOrder.customer', 'saleOrder.shippingAddress', 'saleOrder.items.product',
            'deliveryBoy.user', 'assignedBy',
            'confirmation.deliveryReportedBy', 'confirmation.customerConfirmedBy',
            'confirmation.disputedBy', 'confirmation.resolvedBy',
        ];
    }
}
