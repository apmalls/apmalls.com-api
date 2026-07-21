<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Cart\CartItem;
use Illuminate\Database\Eloquent\Collection;

interface CartItemRepositoryInterface
{
    /**
     * Cart Items
     */
    public function items(
        int $cartId
    ): Collection;

    /**
     * Find Item
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
     * Add Item
     */
    public function create(
        array $data
    ): CartItem;

    /**
     * Update Item
     */
    public function update(
        int $id,
        array $data
    ): CartItem;

    /**
     * Delete Item
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Clear Cart
     */
    public function clear(
        int $cartId
    ): bool;

    /**
     * Total Quantity
     */
    public function totalQuantity(
        int $cartId
    ): int;

    /**
     * Cart Subtotal
     */
    public function subtotal(
        int $cartId
    ): float;
}
