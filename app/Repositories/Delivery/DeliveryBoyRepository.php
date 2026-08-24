<?php

declare(strict_types=1);

namespace App\Repositories\Delivery;

use App\Models\Delivery\DeliveryBoy;
use App\Repositories\Contracts\DeliveryBoyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DeliveryBoyRepository implements DeliveryBoyRepositoryInterface
{
    public function paginate(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {

        $query = DeliveryBoy::query()->with('user');

        if (! empty($filters['search'])) {

            $search = $filters['search'];

            $query->where(function ($query) use ($search) {

                $query->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {

                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");

                    });

            });

        }

        if (array_key_exists('is_active', $filters)) {

            $query->where(
                'is_active',
                $filters['is_active']
            );

        }

        if (array_key_exists('is_available', $filters)) {

            $query->where(
                'is_available',
                $filters['is_available']
            );

        }

        return $query
            ->latest('id')
            ->paginate($perPage);
    }

    public function getAll(): Collection
    {
        return DeliveryBoy::with('user')
            ->latest('id')
            ->get();
    }

    public function findById(
        int $id
    ): ?DeliveryBoy {

        return DeliveryBoy::with('user')
            ->find($id);

    }

    public function findByUserId(
        int $userId
    ): ?DeliveryBoy {

        return DeliveryBoy::where(
            'user_id',
            $userId
        )->first();

    }

    public function create(
        array $data
    ): DeliveryBoy {

        return DeliveryBoy::create($data);

    }

    public function update(
        DeliveryBoy $deliveryBoy,
        array $data
    ): DeliveryBoy {

        $deliveryBoy->update($data);

        return $deliveryBoy->fresh();
    }

    public function delete(
        DeliveryBoy $deliveryBoy
    ): bool {

        return (bool) $deliveryBoy->delete();

    }

    public function updateAvailability(
        DeliveryBoy $deliveryBoy,
        bool $available
    ): bool {

        return $deliveryBoy->update([
            'is_available' => $available,
        ]);

    }

    public function updateLocation(
        DeliveryBoy $deliveryBoy,
        float $latitude,
        float $longitude
    ): bool {

        return $deliveryBoy->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
        ]);

    }
}
