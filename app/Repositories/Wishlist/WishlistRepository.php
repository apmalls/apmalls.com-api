<?php

declare(strict_types=1);

namespace App\Repositories\Wishlist;


use App\Models\Wishlist\Wishlist;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\WishlistRepositoryInterface;

class WishlistRepository implements WishlistRepositoryInterface
{
    /**
     * Customer Wishlist
     */
    public function index(
        int $customerId
    ): Collection {

        return Wishlist::query()

            ->with([
                'product',
                'product.images',
                'product.brand',
                'product.category',
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
     * Find Wishlist Item
     */
    public function find(
        int $id
    ): Wishlist {

        return Wishlist::query()

            ->with([
                'product',
                'product.images',
                'product.brand',
                'product.category',
                'product.unit',
            ])

            ->findOrFail($id);

    }

    /**
     * Find Product In Wishlist
     */
    public function findByProduct(
        int $customerId,
        int $productId
    ): ?Wishlist {

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
     * Add Product To Wishlist
     */
    public function create(
        array $data
    ): Wishlist {

        return Wishlist::create($data);

    }

    /**
     * Delete Wishlist Item
     */
    public function delete(
        int $id
    ): bool {

        return (bool) $this->find($id)
            ->delete();

    }

    /**
     * Clear Wishlist
     */
    public function clear(
        int $customerId
    ): bool {

        return (bool) Wishlist::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->delete();

    }

    /**
     * Wishlist Count
     */
    public function count(
        int $customerId
    ): int {

        return Wishlist::query()

            ->where(
                'customer_id',
                $customerId
            )

            ->count();

    }
}
