<?php

declare(strict_types=1);

namespace App\Services\Website;


use App\Models\Wishlist\Wishlist;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Contracts\WishlistServiceInterface;
use App\Repositories\Contracts\WishlistRepositoryInterface;

class WishlistService implements WishlistServiceInterface
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected WishlistRepositoryInterface $wishlistRepository,
    ) {}

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
    ): Collection
    {
        return $this->wishlistRepository->index(
            $customerId
        );
    }

    /**
     * Find wishlist item.
     */
    public function find(
        int $id
    ): Wishlist
    {
        return $this->wishlistRepository->find(
            $id
        );
    }

    /**
     * Find product in wishlist.
     */
    public function findByProduct(
        int $customerId,
        int $productId
    ): ?Wishlist
    {
        return $this->wishlistRepository->findByProduct(
            $customerId,
            $productId
        );
    }

    /**
     * Check product exists in wishlist.
     */
    public function exists(
        int $customerId,
        int $productId
    ): bool
    {
        return $this->wishlistRepository->exists(
            $customerId,
            $productId
        );
    }

    /**
     * Add product to wishlist.
     */
    public function create(
        array $data
    ): Wishlist
    {
        return $this->wishlistRepository->create(
            $data
        );
    }

    /**
     * Remove wishlist item.
     */
    public function delete(
        int $customerId,
        int $productId
    ): bool
    {
        return $this->wishlistRepository->delete(
            $customerId,
            $productId
        );
    }

    /**
     * Clear customer wishlist.
     */
    public function clear(
        int $customerId
    ): bool
    {
        return $this->wishlistRepository->clear(
            $customerId
        );
    }

    /**
     * Wishlist items count.
     */
    public function count(
        int $customerId
    ): int
    {
        return $this->wishlistRepository->count(
            $customerId
        );
    }
}
