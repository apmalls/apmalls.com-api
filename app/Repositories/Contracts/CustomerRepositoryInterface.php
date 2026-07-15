<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Customer\Customer;

interface CustomerRepositoryInterface
{
    /**
     * Find Customer
     */
    public function find(
        int $id
    ): Customer;

    /**
     * Find By User
     */
    public function findByUser(
        int $userId
    ): ?Customer;

    /**
     * Create Customer
     */
    public function create(
        array $data
    ): Customer;

    /**
     * Update Customer
     */
    public function update(
        int $id,
        array $data
    ): Customer;
}
