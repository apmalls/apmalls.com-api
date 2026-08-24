<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Delivery\DeliveryAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DeliveryAssignmentRepositoryInterface
{
    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator;

    public function create(
        array $data
    ): DeliveryAssignment;

    public function update(
        DeliveryAssignment $assignment,
        array $data
    ): DeliveryAssignment;

    public function findById(
        int $id
    ): ?DeliveryAssignment;

    public function findActiveAssignment(
        int $saleOrderId
    ): ?DeliveryAssignment;

    public function getOrderAssignments(
        int $saleOrderId
    ): Collection;

    public function getDeliveryBoyAssignments(
        int $deliveryBoyId,
        array $filters = []
    ): Collection;

    public function delete(
        DeliveryAssignment $assignment
    ): bool;
}
