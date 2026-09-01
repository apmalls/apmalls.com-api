<?php

declare(strict_types=1);

namespace App\Repositories\Delivery;

use App\Models\Delivery\DeliveryAssignment;
use App\Repositories\Contracts\DeliveryAssignmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DeliveryAssignmentRepository implements DeliveryAssignmentRepositoryInterface
{
    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {

        $query = DeliveryAssignment::query()
            ->with([
                'saleOrder.customer',
                'saleOrder.shippingAddress',
                'saleOrder.items.product',
                'deliveryBoy.user',
                'assignedBy',
                'confirmation.deliveryReportedBy',
                'confirmation.customerConfirmedBy',
                'confirmation.disputedBy',
                'confirmation.resolvedBy',
            ]);

        if (! empty($filters['status'])) {

            $query->where(
                'status',
                $filters['status']
            );

        }

        if (! empty($filters['delivery_boy_id'])) {

            $query->where(
                'delivery_boy_id',
                $filters['delivery_boy_id']
            );

        }

        if (! empty($filters['sale_order_id'])) {

            $query->where(
                'sale_order_id',
                $filters['sale_order_id']
            );

        }

        if (! empty($filters['confirmation_status'])) {
            $query->whereHas('confirmation', fn ($confirmation) => $confirmation
                ->where('status', $filters['confirmation_status']));
        }

        return $query
            ->latest('id')
            ->paginate($perPage);
    }

    public function create(
        array $data
    ): DeliveryAssignment {

        return DeliveryAssignment::create($data);

    }

    public function update(
        DeliveryAssignment $assignment,
        array $data
    ): DeliveryAssignment {

        $assignment->update($data);

        return $assignment->fresh();

    }

    public function findById(
        int $id
    ): ?DeliveryAssignment {

        return DeliveryAssignment::with([
            'saleOrder.customer',
            'saleOrder.shippingAddress',
            'saleOrder.items.product',
            'deliveryBoy.user',
            'assignedBy',
            'confirmation.deliveryReportedBy',
            'confirmation.customerConfirmedBy',
            'confirmation.disputedBy',
            'confirmation.resolvedBy',
        ])->find($id);

    }

    public function findActiveAssignment(
        int $saleOrderId
    ): ?DeliveryAssignment {

        return DeliveryAssignment::where(
                'sale_order_id',
                $saleOrderId
            )
            ->whereNotIn('status', [
                DeliveryAssignment::STATUS_REJECTED,
                DeliveryAssignment::STATUS_CANCELLED,
                DeliveryAssignment::STATUS_DELIVERED,
            ])
            ->latest('id')
            ->first();

    }

    public function getOrderAssignments(
        int $saleOrderId
    ): Collection {

        return DeliveryAssignment::with([
                'deliveryBoy.user',
                'assignedBy',
                'confirmation.deliveryReportedBy',
                'confirmation.customerConfirmedBy',
                'confirmation.disputedBy',
                'confirmation.resolvedBy',
            ])
            ->where(
                'sale_order_id',
                $saleOrderId
            )
            ->latest('id')
            ->get();

    }

    public function getDeliveryBoyAssignments(
        int $deliveryBoyId,
        array $filters = []
    ): Collection {

        $query = DeliveryAssignment::with([
                'saleOrder.customer',
                'saleOrder.shippingAddress',
                'saleOrder.items.product',
                'confirmation.deliveryReportedBy',
                'confirmation.customerConfirmedBy',
                'confirmation.disputedBy',
                'confirmation.resolvedBy',
            ])
            ->where(
                'delivery_boy_id',
                $deliveryBoyId
            );

        if (! empty($filters['status'])) {

            $query->where(
                'status',
                $filters['status']
            );

        }

        return $query
            ->latest('id')
            ->get();

    }

    public function delete(
        DeliveryAssignment $assignment
    ): bool {

        return (bool) $assignment->delete();

    }
}
