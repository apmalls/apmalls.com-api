<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Models\Delivery\DeliveryBoy;
use App\Repositories\Contracts\DeliveryBoyRepositoryInterface;
use App\Services\Contracts\DeliveryBoyServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeliveryBoyService implements DeliveryBoyServiceInterface
{
    public function __construct(
        protected DeliveryBoyRepositoryInterface $deliveryBoyRepository,
    ) {
    }

    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {

        return $this->deliveryBoyRepository
            ->paginate($filters, $perPage);

    }

    public function getAll(): Collection
    {
        return $this->deliveryBoyRepository
            ->getAll();
    }

    public function findById(
        int $id
    ): ?DeliveryBoy {

        return $this->deliveryBoyRepository
            ->findById($id);

    }

    public function create(
        array $data
    ): DeliveryBoy {

        return $this->deliveryBoyRepository
            ->create($data);

    }

    public function update(
        int $id,
        array $data
    ): DeliveryBoy {

        $deliveryBoy = $this->deliveryBoyRepository
            ->findById($id);

        if (! $deliveryBoy) {

            throw new ModelNotFoundException(
                'Delivery boy not found.'
            );

        }

        return $this->deliveryBoyRepository
            ->update(
                $deliveryBoy,
                $data
            );

    }

    public function delete(
        int $id
    ): bool {

        $deliveryBoy = $this->deliveryBoyRepository
            ->findById($id);

        if (! $deliveryBoy) {

            throw new ModelNotFoundException(
                'Delivery boy not found.'
            );

        }

        return $this->deliveryBoyRepository
            ->delete($deliveryBoy);

    }

    public function updateAvailability(
        int $id,
        bool $available
    ): bool {

        $deliveryBoy = $this->deliveryBoyRepository
            ->findById($id);

        if (! $deliveryBoy) {

            throw new ModelNotFoundException(
                'Delivery boy not found.'
            );

        }

        return $this->deliveryBoyRepository
            ->updateAvailability(
                $deliveryBoy,
                $available
            );

    }

    public function updateLocation(
        int $id,
        float $latitude,
        float $longitude
    ): bool {

        $deliveryBoy = $this->deliveryBoyRepository
            ->findById($id);

        if (! $deliveryBoy) {

            throw new ModelNotFoundException(
                'Delivery boy not found.'
            );

        }

        return $this->deliveryBoyRepository
            ->updateLocation(
                $deliveryBoy,
                $latitude,
                $longitude
            );

    }
}
