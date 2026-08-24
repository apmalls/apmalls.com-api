<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Cart\CartItem;
use Illuminate\Database\Eloquent\Collection;

interface CartItemRepositoryInterface
{
    /**
     * Get all items.
     */
    public function all(
        int $cartId
    ): Collection;

    /**
     * Find item.
     */
    public function find(
        int $id
    ): CartItem;

    /**
     * Find Item with row lock (must be called inside a transaction)
     */
    public function findForUpdate(
        int $id
    ): CartItem;

    /**
     * Find Product In Cart
     */
    public function findByProduct(
        int $cartId,
        int $productId
    ): ?CartItem;

    /**
     * Check product exists.
     */
    public function exists(
        int $cartId,
        int $productId
    ): bool;

    /**
     * Create item.
     */
    public function create(
        array $data
    ): CartItem;

    /**
     * Update item.
     */
    public function update(
        int $id,
        array $data
    ): bool;

    /**
     * Delete item.
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Clear cart.
     */
    public function clear(
        int $cartId
    ): bool;

    /**
     * Count items.
     */
    public function count(
        int $cartId
    ): int;
}
