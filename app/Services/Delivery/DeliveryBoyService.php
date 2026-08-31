<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Models\Delivery\DeliveryBoy;
use App\Models\Delivery\DeliveryAssignment;
use App\Models\User;
use App\Repositories\Contracts\DeliveryBoyRepositoryInterface;
use App\Services\Contracts\DeliveryBoyServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
        return DB::transaction(function () use ($data) {
            $user = ! empty($data['user_id'])
                ? User::findOrFail($data['user_id'])
                : User::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'] ?? null,
                    'email' => $data['email'],
                    'mobile' => $data['phone'],
                    'password' => $data['password'],
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);

            if ($user->deliveryBoy()->exists()) {
                throw ValidationException::withMessages(['user_id' => ['This user already has a delivery profile.']]);
            }

            $user->syncRoles('Delivery Boy');
            $profileData = Arr::only($data, [
                'employee_code', 'phone', 'alternate_phone', 'vehicle_type', 'vehicle_number',
                'license_number', 'aadhaar_no', 'pan_no', 'address', 'is_available', 'is_active',
            ]);
            $profileData['user_id'] = $user->id;

            if (($data['photo'] ?? null) instanceof \Illuminate\Http\UploadedFile) {
                $profileData['photo'] = $data['photo']->store('delivery-boys', 'public');
            }

            $profile = $this->deliveryBoyRepository->create($profileData);

            return $profile->fresh('user');
        });

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

        return DB::transaction(function () use ($deliveryBoy, $data) {
            if (array_key_exists('is_active', $data) && ! $data['is_active']) {
                $hasActive = $deliveryBoy->assignments()
                    ->whereIn('status', [
                        DeliveryAssignment::STATUS_ASSIGNED,
                        DeliveryAssignment::STATUS_ACCEPTED,
                        DeliveryAssignment::STATUS_PICKED,
                        DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
                    ])->exists();
                if ($hasActive) {
                    throw ValidationException::withMessages(['is_active' => ['Reassign or cancel active deliveries before deactivating this profile.']]);
                }
            }

            if (($data['photo'] ?? null) instanceof \Illuminate\Http\UploadedFile) {
                if ($deliveryBoy->photo) {
                    Storage::disk('public')->delete($deliveryBoy->photo);
                }
                $data['photo'] = $data['photo']->store('delivery-boys', 'public');
            }

            $updated = $this->deliveryBoyRepository->update($deliveryBoy, $data);
            if (array_key_exists('is_active', $data)) {
                $deliveryBoy->user()->update(['is_active' => (bool) $data['is_active']]);
            }

            return $updated->fresh('user');
        });

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

        return DB::transaction(function () use ($deliveryBoy) {
            $this->update($deliveryBoy->id, ['is_active' => false, 'is_available' => false]);
            return $this->deliveryBoyRepository->delete($deliveryBoy);
        });

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
