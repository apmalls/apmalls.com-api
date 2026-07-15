<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Customer\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;

interface CustomerAddressRepositoryInterface
{
    /**
     * Customer Addresses
     */
    public function index(
        int $customerId
    ): Collection;

    /**
     * Find Address
     */
    public function find(
        int $id
    ): CustomerAddress;

    /**
     * Find Customer Address
     */
    public function findByCustomer(
        int $customerId,
        int $addressId
    ): CustomerAddress;

    /**
     * Default Address
     */
    public function default(
        int $customerId
    ): ?CustomerAddress;

    /**
     * Create Address
     */
    public function create(
        array $data
    ): CustomerAddress;

    /**
     * Update Address
     */
    public function update(
        int $id,
        array $data
    ): CustomerAddress;

    /**
     * Delete Address
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Remove Default Address
     */
    public function clearDefault(
        int $customerId
    ): void;
}
