<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Models\Delivery\DeliveryAssignment;
use App\Models\Delivery\DeliveryConfirmation;
use App\Models\Sale\SaleOrder;

use App\Repositories\Contracts\DeliveryAssignmentRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Services\Contracts\DeliveryAssignmentServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryAssignmentService implements DeliveryAssignmentServiceInterface
{
    public function __construct(
        protected DeliveryAssignmentRepositoryInterface $deliveryAssignmentRepository,
        protected SaleRepositoryInterface $saleOrderRepository,

    ) {
    }

    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {

        return $this->deliveryAssignmentRepository
            ->paginate($filters, $perPage);

    }

    public function findById(
        int $id
    ): ?DeliveryAssignment {

        return $this->deliveryAssignmentRepository
            ->findById($id);

    }

    public function assignOrder(
        array $data
    ): DeliveryAssignment {

        return DB::transaction(function () use ($data) {

            $order = SaleOrder::query()->lockForUpdate()->findOrFail($data['sale_order_id']);
            $deliveryBoy = \App\Models\Delivery\DeliveryBoy::query()->findOrFail($data['delivery_boy_id']);

            if ($order->status !== SaleOrder::STATUS_CONFIRMED || ! $order->shipping_address_id) {
                throw ValidationException::withMessages(['sale_order_id' => ['Only confirmed orders with a shipping address can be assigned.']]);
            }

            if (! $deliveryBoy->is_active || ! $deliveryBoy->is_available || ! $deliveryBoy->user?->is_active) {
                throw ValidationException::withMessages(['delivery_boy_id' => ['Select an active and available delivery person.']]);
            }

            if ($this->deliveryAssignmentRepository->findActiveAssignment($order->id)) {
                throw ValidationException::withMessages(['sale_order_id' => ['This order already has an active delivery assignment.']]);
            }

            $assignment = $this->deliveryAssignmentRepository
                ->create([
                    ...$data,
                    'status' => DeliveryAssignment::STATUS_ASSIGNED,
                    'assigned_at' => now(),
                ]);

            $this->saleOrderRepository
                ->updateDeliveryStatus(
                    $assignment->sale_order_id,
                    DeliveryAssignment::STATUS_ASSIGNED
                );

            return $assignment->load([
                'saleOrder.customer', 'saleOrder.shippingAddress', 'saleOrder.items.product',
                'deliveryBoy.user', 'assignedBy',
            ]);

        });

    }

    public function accept(
        int $assignmentId
    ): DeliveryAssignment {

        return $this->changeStatus(
            $assignmentId,
            DeliveryAssignment::STATUS_ACCEPTED,
            'accepted_at'
        );

    }

    public function reject(
        int $assignmentId,
        ?string $remarks = null
    ): DeliveryAssignment {

        return DB::transaction(function () use ($assignmentId, $remarks) {

            $assignment = $this->getAssignment($assignmentId);

            if ($assignment->status !== DeliveryAssignment::STATUS_ASSIGNED) {
                throw ValidationException::withMessages(['status' => ['Only an assigned delivery can be rejected.']]);
            }

            if (blank($remarks)) {
                throw ValidationException::withMessages(['remarks' => ['Remarks are required when rejecting an assignment.']]);
            }

            $assignment = $this->deliveryAssignmentRepository
                ->update(
                    $assignment,
                    [
                        'status' => DeliveryAssignment::STATUS_REJECTED,
                        'remarks' => $remarks,
                        'rejected_at' => now(),
                    ]
                );

            $this->saleOrderRepository
                ->updateDeliveryStatus(
                    $assignment->sale_order_id,
                    null
                );

            return $assignment;

        });

    }

    public function pickup(
        int $assignmentId
    ): DeliveryAssignment {

        return $this->changeStatus(
            $assignmentId,
            DeliveryAssignment::STATUS_PICKED,
            'picked_at'
        );

    }

    public function outForDelivery(
        int $assignmentId
    ): DeliveryAssignment {

        return $this->changeStatus(
            $assignmentId,
            DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
            'out_for_delivery_at'
        );

    }

    public function delivered(
        int $assignmentId
    ): DeliveryAssignment {
        throw ValidationException::withMessages([
            'delivery' => ['Customer or manager confirmation is required to complete delivery.'],
        ]);

    }

    public function history(
        int $saleOrderId
    ): Collection {

        return $this->deliveryAssignmentRepository
            ->getOrderAssignments($saleOrderId);

    }

    public function delete(
        int $assignmentId
    ): bool {
        return DB::transaction(function () use ($assignmentId) {
            $assignment = DeliveryAssignment::query()
                ->lockForUpdate()
                ->findOrFail($assignmentId);

            if ($assignment->status === DeliveryAssignment::STATUS_DELIVERED) {
                throw ValidationException::withMessages(['status' => ['A delivered assignment cannot be cancelled.']]);
            }

            if ($assignment->status === DeliveryAssignment::STATUS_REJECTED) {
                throw ValidationException::withMessages(['status' => ['A rejected assignment is already closed.']]);
            }

            if ($assignment->status === DeliveryAssignment::STATUS_CANCELLED) {
                return true;
            }

            $confirmation = DeliveryConfirmation::query()
                ->where('delivery_assignment_id', $assignment->id)
                ->first();
            if ($confirmation && in_array($confirmation->status, [
                DeliveryConfirmation::STATUS_AWAITING_CUSTOMER,
                DeliveryConfirmation::STATUS_DISPUTED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Resolve the delivery confirmation before cancelling this assignment.'],
                ]);
            }

            $this->deliveryAssignmentRepository->update($assignment, [
                'status' => DeliveryAssignment::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);
            $this->saleOrderRepository->updateDeliveryStatus($assignment->sale_order_id, null);

            return true;
        });

    }

    /**
     * Get Assignment.
     */
    protected function getAssignment(
        int $assignmentId
    ): DeliveryAssignment {

        $assignment = $this->deliveryAssignmentRepository
            ->findById($assignmentId);

        if (!$assignment) {

            throw new ModelNotFoundException(
                'Delivery assignment not found.'
            );

        }

        return $assignment;

    }

    /**
     * Change Status.
     */
    protected function changeStatus(
        int $assignmentId,
        string $status,
        string $timestampColumn
    ): DeliveryAssignment {

        return DB::transaction(function () use ($assignmentId, $status, $timestampColumn) {

            $assignment = DeliveryAssignment::query()
                ->lockForUpdate()
                ->findOrFail($assignmentId);

            $expected = match ($status) {
                DeliveryAssignment::STATUS_ACCEPTED => DeliveryAssignment::STATUS_ASSIGNED,
                DeliveryAssignment::STATUS_PICKED => DeliveryAssignment::STATUS_ACCEPTED,
                DeliveryAssignment::STATUS_OUT_FOR_DELIVERY => DeliveryAssignment::STATUS_PICKED,
                default => DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
            };

            if ($assignment->status !== $expected) {
                throw ValidationException::withMessages(['status' => ['Invalid delivery status transition.']]);
            }

            $assignment = $this->deliveryAssignmentRepository
                ->update(
                    $assignment,
                    [
                        'status' => $status,
                        $timestampColumn => now(),
                    ]
                );

            $this->saleOrderRepository
                ->updateDeliveryStatus(
                    $assignment->sale_order_id,
                    $status
                );

            return $assignment;

        });

    }
}
