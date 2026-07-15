<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Wishlist\Wishlist;
use Illuminate\Database\Eloquent\Collection;

interface WishlistRepositoryInterface
{
    /**
     * Customer Wishlist
     */
    public function index(
        int $customerId
    ): Collection;

    /**
     * Find Wishlist Item
     */
    public function find(
        int $id
    ): Wishlist;

    /**
     * Find Product In Wishlist
     */
    public function findByProduct(
        int $customerId,
        int $productId
    ): ?Wishlist;

    /**
     * Add Product
     */
    public function create(
        array $data
    ): Wishlist;

    /**
     * Delete Wishlist Item
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Clear Wishlist
     */
    public function clear(
        int $customerId
    ): bool;

    /**
     * Wishlist Count
     */
    public function count(
        int $customerId
    ): int;
}
