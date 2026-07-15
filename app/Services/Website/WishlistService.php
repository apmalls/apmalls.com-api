<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Wishlist\Wishlist;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\WishlistRepositoryInterface;

class WishlistService
{
    public function __construct(
        protected WishlistRepositoryInterface $wishlistRepository,
        protected ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * Customer Wishlist
     */
    public function index(
        int $customerId
    ): Collection {

        return $this->wishlistRepository
            ->index($customerId);

    }

    /**
     * Add Product To Wishlist
     */
    public function add(
        int $customerId,
        array $data
    ): Wishlist {

        return DB::transaction(function () use ($customerId, $data) {

            $product = $this->productRepository
                ->find($data['product_id']);

            $wishlist = $this->wishlistRepository
                ->findByProduct(
                    $customerId,
                    $product->id
                );

            if ($wishlist) {

                return $wishlist;

            }

            return $this->wishlistRepository
                ->create([

                    'customer_id' => $customerId,

                    'product_id' => $product->id,

                    'remarks' => $data['remarks'] ?? null,

                ]);

        });

    }

    /**
     * Remove Wishlist Item
     */
    public function remove(
        int $wishlistId
    ): bool {

        return $this->wishlistRepository
            ->delete($wishlistId);

    }

    /**
     * Clear Wishlist
     */
    public function clear(
        int $customerId
    ): bool {

        return $this->wishlistRepository
            ->clear($customerId);

    }

    /**
     * Wishlist Count
     */
    public function count(
        int $customerId
    ): int {

        return $this->wishlistRepository
            ->count($customerId);

    }
}
