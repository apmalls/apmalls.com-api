<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Customer\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CustomerAddressService
{
    public function index(int $customerId): Collection
    {
        return CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('is_default')
            ->latest()
            ->get();
    }

    public function store(int $customerId, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($customerId, $data): CustomerAddress {
            $hasAddress = CustomerAddress::query()
                ->where('customer_id', $customerId)
                ->exists();
            $isDefault = ! $hasAddress || (bool) ($data['is_default'] ?? false);

            if ($isDefault) {
                $this->clearDefault($customerId);
            }

            return CustomerAddress::query()->create([
                ...$data,
                'customer_id' => $customerId,
                'is_default' => $isDefault,
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function update(int $customerId, int $id, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($customerId, $id, $data): CustomerAddress {
            $address = $this->findOwned($customerId, $id);
            $isDefault = $address->is_default || (bool) ($data['is_default'] ?? false);

            if ($isDefault) {
                $this->clearDefault($customerId, $address->id);
            }

            $address->update([
                ...$data,
                'is_default' => $isDefault,
                'updated_by' => auth()->id(),
            ]);

            return $address->refresh();
        });
    }

    public function delete(int $customerId, int $id): void
    {
        DB::transaction(function () use ($customerId, $id): void {
            $address = $this->findOwned($customerId, $id);
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                CustomerAddress::query()
                    ->where('customer_id', $customerId)
                    ->latest()
                    ->first()
                    ?->update([
                        'is_default' => true,
                        'updated_by' => auth()->id(),
                    ]);
            }
        });
    }

    public function default(int $customerId): ?CustomerAddress
    {
        return CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->where('is_default', true)
            ->first();
    }

    public function setDefault(int $customerId, int $id): CustomerAddress
    {
        return DB::transaction(function () use ($customerId, $id): CustomerAddress {
            $address = $this->findOwned($customerId, $id);
            $this->clearDefault($customerId, $address->id);
            $address->update([
                'is_default' => true,
                'updated_by' => auth()->id(),
            ]);

            return $address->refresh();
        });
    }

    private function findOwned(int $customerId, int $id): CustomerAddress
    {
        return CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->findOrFail($id);
    }

    private function clearDefault(int $customerId, ?int $exceptId = null): void
    {
        CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('is_default', true)
            ->update([
                'is_default' => false,
                'updated_by' => auth()->id(),
            ]);
    }
}
