<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Delivery\DeliveryAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DeliveryAssignmentServiceInterface
{
    /**
     * Paginated assignment listing.
     */
    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * Find assignment by ID.
     */
    public function findById(
        int $id
    ): ?DeliveryAssignment;

    /**
     * Assign order to delivery boy.
     */
    public function assignOrder(
        array $data
    ): DeliveryAssignment;

    /**
     * Accept assigned order.
     */
    public function accept(
        int $assignmentId
    ): DeliveryAssignment;

    /**
     * Reject assigned order.
     */
    public function reject(
        int $assignmentId,
        ?string $remarks = null
    ): DeliveryAssignment;

    /**
     * Mark order as picked.
     */
    public function pickup(
        int $assignmentId
    ): DeliveryAssignment;

    /**
     * Mark order as out for delivery.
     */
    public function outForDelivery(
        int $assignmentId
    ): DeliveryAssignment;

    /**
     * Mark order as delivered.
     */
    public function delivered(
        int $assignmentId
    ): DeliveryAssignment;

    /**
     * Get assignment history of an order.
     */
    public function history(
        int $saleOrderId
    ): Collection;

    /**
     * Delete assignment.
     */
    public function delete(
        int $assignmentId
    ): bool;
}
