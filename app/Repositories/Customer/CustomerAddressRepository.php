<?php

declare(strict_types=1);

namespace App\Repositories\Customer;

use App\Models\Customer\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\CustomerAddressRepositoryInterface;

class CustomerAddressRepository implements CustomerAddressRepositoryInterface
{
    /**
     * Customer Address Listing
     */
    public function index(
        int $customerId
    ): Collection {

        return CustomerAddress::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->latest()

            ->get();

    }

    /**
     * Find Address
     */
    public function find(
        int $id
    ): CustomerAddress {

        return CustomerAddress::query()

            ->findOrFail($id);

    }

    /**
     * Find Customer Address
     */
    public function findByCustomer(
        int $customerId,
        int $addressId
    ): CustomerAddress {

        return CustomerAddress::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->findOrFail(
                $addressId
            );

    }

    /**
     * Default Address
     */
    public function default(
        int $customerId
    ): ?CustomerAddress {

        return CustomerAddress::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->where(
                'is_default',
                true
            )

            ->first();

    }

    /**
     * Create Address
     */
    public function create(
        array $data
    ): CustomerAddress {

        return CustomerAddress::create($data);

    }

    /**
     * Update Address
     */
    public function update(
        int $id,
        array $data
    ): CustomerAddress {

        $address = $this->find($id);

        $address->update($data);

        return $address->refresh();

    }

    /**
     * Delete Address
     */
    public function delete(
        int $id
    ): bool {

        return (bool) $this->find($id)
            ->delete();

    }

    /**
     * Remove Default Address
     */
    public function clearDefault(
        int $customerId
    ): void {

        CustomerAddress::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->update([

                'is_default' => false,

            ]);

    }
}
