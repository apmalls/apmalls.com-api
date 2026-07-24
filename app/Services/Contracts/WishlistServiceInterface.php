<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Wishlist\Wishlist;
use Illuminate\Database\Eloquent\Collection;

interface WishlistServiceInterface
{
    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    /**
     * Customer wishlist.
     */
    public function index(
        int $customerId
    ): Collection;

    /**
     * Find wishlist item.
     */
    public function find(
        int $id
    ): Wishlist;

    /**
     * Find product in wishlist.
     */
    public function findByProduct(
        int $customerId,
        int $productId
    ): ?Wishlist;

    /**
     * Check product exists in wishlist.
     */
    public function exists(
        int $customerId,
        int $productId
    ): bool;

    /**
     * Add product to wishlist.
     */
    public function create(
        array $data
    ): Wishlist;

    /**
     * Remove wishlist item.
     */
    public function delete(
        int $customerId,
        int $productId
    ): bool;

    /**
     * Clear customer wishlist.
     */
    public function clear(
        int $customerId
    ): bool;

    /**
     * Wishlist items count.
     */
    public function count(
        int $customerId
    ): int;
}
