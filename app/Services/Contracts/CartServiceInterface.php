<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Cart\Cart;

interface CartServiceInterface
{
    /**
     * Customer active cart.
     */
    public function getActiveCart(
        int $customerId
    ): ?Cart;

    /**
     * Add product to cart.
     */
    public function addItem(
        int $customerId,
        int $productId,
        int $quantity = 1
    ): Cart;

    /**
     * Update cart quantity.
     */
    public function updateQuantity(
        int $customerId,
        int $productId,
        int $quantity
    ): Cart;

    /**
     * Remove item.
     */
    public function removeItem(
        int $customerId,
        int $productId
    ): Cart;

    /**
     * Clear cart.
     */
    public function clear(
        int $customerId
    ): bool;

    /**
     * Apply coupon.
     */
    public function applyCoupon(
        int $customerId,
        string $couponCode
    ): Cart;

    /**
     * Remove coupon.
     */
    public function removeCoupon(
        int $customerId
    ): Cart;

    /**
     * Cart count.
     */
    public function count(
        int $customerId
    ): int;
}
