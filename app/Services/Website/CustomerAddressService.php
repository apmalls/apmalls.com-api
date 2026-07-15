<?php

declare(strict_types=1);

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Customer\CustomerAddress;
use App\Repositories\Contracts\CustomerAddressRepositoryInterface;

class CustomerAddressService
{
    public function __construct(
        protected CustomerAddressRepositoryInterface $addressRepository,
    ) {
    }

    /**
     * Customer Address Listing
     */
    public function index(
        int $customerId
    ): Collection {

        return $this->addressRepository
            ->index($customerId);

    }

    /**
     * Store Address
     */
    public function store(
        int $customerId,
        array $data
    ): CustomerAddress {

        return DB::transaction(function () use ($customerId, $data) {

            if ($data['is_default'] ?? false) {

                $this->addressRepository
                    ->clearDefault($customerId);

            }

            $data['customer_id'] = $customerId;

            return $this->addressRepository
                ->create($data);

        });

    }

    /**
     * Update Address
     */
    public function update(
        int $customerId,
        int $addressId,
        array $data
    ): CustomerAddress {

        return DB::transaction(function () use ($customerId, $addressId, $data) {

            $this->addressRepository
                ->findByCustomer(
                    $customerId,
                    $addressId
                );

            if ($data['is_default'] ?? false) {

                $this->addressRepository
                    ->clearDefault($customerId);

            }

            return $this->addressRepository
                ->update(
                    $addressId,
                    $data
                );

        });

    }

    /**
     * Delete Address
     */
    public function delete(
        int $customerId,
        int $addressId
    ): bool {

        $this->addressRepository
            ->findByCustomer(
                $customerId,
                $addressId
            );

        return $this->addressRepository
            ->delete(
                $addressId
            );

    }

    /**
     * Default Address
     */
    public function default(
        int $customerId
    ): ?CustomerAddress {

        return $this->addressRepository
            ->default($customerId);

    }
}
