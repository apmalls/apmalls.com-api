<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Cart\Cart;

interface CartRepositoryInterface
{
    /**
     * Get active cart.
     */
    public function getActiveCart(
        int $customerId
    ): ?Cart;

    /**
     * Find cart.
     */
    public function find(
        int $id
    ): Cart;

    /**
     * Create cart.
     */
    public function create(
        array $data
    ): Cart;

    /**
     * Update cart.
     */
    public function update(
        int $id,
        array $data
    ): bool;

    /**
     * Delete cart.
     */
    public function delete(
        int $id
    ): bool;
}
