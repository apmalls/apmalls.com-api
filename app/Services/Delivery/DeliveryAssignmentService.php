<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Models\Delivery\DeliveryAssignment;
use App\Models\Sale\SaleOrder;

use App\Repositories\Contracts\DeliveryAssignmentRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Services\Contracts\DeliveryAssignmentServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

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

            return $assignment;

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
                    DeliveryAssignment::STATUS_ASSIGNED
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

        return DB::transaction(function () use ($assignmentId) {

            $assignment = $this->getAssignment($assignmentId);

            $assignment = $this->deliveryAssignmentRepository
                ->update(
                    $assignment,
                    [
                        'status' => DeliveryAssignment::STATUS_DELIVERED,
                        'delivered_at' => now(),
                    ]
                );

            $this->saleOrderRepository
                ->updateDeliveryStatus(
                    $assignment->sale_order_id,
                    DeliveryAssignment::STATUS_DELIVERED,
                    now()->toDateTimeString()
                );

            return $assignment;

        });

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

        $assignment = $this->getAssignment($assignmentId);

        return $this->deliveryAssignmentRepository
            ->delete($assignment);

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

            $assignment = $this->getAssignment($assignmentId);

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
