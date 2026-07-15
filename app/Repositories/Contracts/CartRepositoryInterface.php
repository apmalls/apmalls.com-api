<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Cart\Cart;

interface CartRepositoryInterface
{
    /**
     * Get Active Cart
     */
    public function active(
        int $customerId
    ): ?Cart;

    /**
     * Find Cart
     */
    public function find(
        int $id
    ): Cart;

    /**
     * Create Cart
     */
    public function create(
        array $data
    ): Cart;

    /**
     * Update Cart
     */
    public function update(
        int $id,
        array $data
    ): Cart;

    /**
     * Delete Cart
     */
    public function delete(
        int $id
    ): bool;
}
