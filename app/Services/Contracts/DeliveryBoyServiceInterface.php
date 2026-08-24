<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Delivery\DeliveryBoy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DeliveryBoyServiceInterface
{
    /**
     * Paginated delivery boys listing.
     */
    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * Get all delivery boys.
     */
    public function getAll(): Collection;

    /**
     * Find delivery boy by ID.
     */
    public function findById(
        int $id
    ): ?DeliveryBoy;

    /**
     * Create a new delivery boy.
     */
    public function create(
        array $data
    ): DeliveryBoy;

    /**
     * Update delivery boy.
     */
    public function update(
        int $id,
        array $data
    ): DeliveryBoy;

    /**
     * Delete delivery boy.
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Update delivery boy availability.
     */
    public function updateAvailability(
        int $id,
        bool $available
    ): bool;

    /**
     * Update delivery boy current location.
     */
    public function updateLocation(
        int $id,
        float $latitude,
        float $longitude
    ): bool;
}
