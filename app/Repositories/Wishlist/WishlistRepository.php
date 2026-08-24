<?php

declare(strict_types=1);

namespace App\Repositories\Wishlist;


use App\Models\Wishlist\Wishlist;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\WishlistRepositoryInterface;

class WishlistRepository implements WishlistRepositoryInterface
{
    /**
     * Customer wishlist.
     */
    public function index(
        int $customerId
    ): Collection
    {
        return Wishlist::query()

            ->with([
                'product',
                'product.images',
                'product.category',
                'product.brand',
                'product.unit',
            ])

            ->where(
                'customer_id',
                $customerId
            )

            ->latest()

            ->get();
    }

    /**
     * Find wishlist item.
     */
    public function find(
        int $id
    ): Wishlist
    {
        return Wishlist::query()

            ->with([
                'product',
                'product.images',
                'product.category',
                'product.brand',
                'product.unit',
            ])

            ->findOrFail($id);
    }

    /**
     * Find product in wishlist.
     */
    public function findByProduct(
        int $customerId,
        int $productId
    ): ?Wishlist
    {
        return Wishlist::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->where(
                'product_id',
                $productId
            )

            ->first();
    }

    /**
     * Check product exists in wishlist.
     */
    public function exists(
        int $customerId,
        int $productId
    ): bool
    {
        return Wishlist::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->where(
                'product_id',
                $productId
            )

            ->exists();
    }

    /**
     * Add product to wishlist.
     */
    public function create(
        array $data
    ): Wishlist
    {
        return Wishlist::query()->create($data);
    }

    /**
     * Remove wishlist item.
     */
    public function delete(
        int $customerId,
        int $productId
    ): bool
    {
        return (bool) Wishlist::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->where(
                'product_id',
                $productId
            )

            ->delete();
    }

    /**
     * Clear customer wishlist.
     */
    public function clear(
        int $customerId
    ): bool
    {
        return (bool) Wishlist::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->delete();
    }

    /**
     * Wishlist items count.
     */
    public function count(
        int $customerId
    ): int
    {
        return Wishlist::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->count();
    }
}
