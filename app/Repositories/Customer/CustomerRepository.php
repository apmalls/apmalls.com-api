<?php

declare(strict_types=1);

namespace App\Repositories\Customer;

use App\Models\Customer\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;

class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * Find Customer
     */
    public function find(
        int $id
    ): Customer {

        return Customer::query()
            ->findOrFail($id);

    }

    /**
     * Find By User
     */
    public function findByUser(
        int $userId
    ): ?Customer {

        return Customer::query()

            ->where(
                'user_id',
                $userId
            )

            ->first();

    }

    /**
     * Create Customer
     */
    public function create(
        array $data
    ): Customer {

        return Customer::create($data);

    }

    /**
     * Update Customer
     */
    public function update(
        int $id,
        array $data
    ): Customer {

        $customer = $this->find($id);

        $customer->update($data);

        return $customer->refresh();

    }

    public function count(): int
    {
        return Customer::query()->count();
    }
}
