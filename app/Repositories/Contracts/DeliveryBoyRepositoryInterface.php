<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Delivery\DeliveryBoy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DeliveryBoyRepositoryInterface
{
    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getAll(): Collection;

    public function findById(int $id): ?DeliveryBoy;

    public function findByUserId(int $userId): ?DeliveryBoy;

    public function create(array $data): DeliveryBoy;

    public function update(
        DeliveryBoy $deliveryBoy,
        array $data
    ): DeliveryBoy;

    public function delete(
        DeliveryBoy $deliveryBoy
    ): bool;

    public function updateAvailability(
        DeliveryBoy $deliveryBoy,
        bool $available
    ): bool;

    public function updateLocation(
        DeliveryBoy $deliveryBoy,
        float $latitude,
        float $longitude
    ): bool;
}
