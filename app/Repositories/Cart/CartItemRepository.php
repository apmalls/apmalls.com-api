<?php

declare(strict_types=1);

namespace App\Repositories\Cart;

use App\Models\Cart\CartItem;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\CartItemRepositoryInterface;

class CartItemRepository implements CartItemRepositoryInterface
{
    /**
     * Get all items.
     */
    public function all(
        int $cartId
    ): Collection {

        return CartItem::query()

            ->with([
                'product',
                'product.images',
                'product.brand',
                'product.category',
                'product.unit',
            ])

            ->where(
                'cart_id',
                $cartId
            )

            ->orderBy('id')

            ->get();

    }

    /**
     * Find Item (row lock for concurrent qty updates)
     */
    public function findForUpdate(
        int $id
    ): CartItem {

        return CartItem::query()

            ->whereKey($id)

            ->lockForUpdate()

            ->firstOrFail();

    }

    /**
     * Find Item
     */
    public function find(
        int $id
    ): CartItem {

        return CartItem::query()

            ->findOrFail(
                $id
            );

    }

    /**
     * Find product in cart.
     */
    public function findByProduct(
        int $cartId,
        int $productId
    ): ?CartItem {

        return CartItem::query()

            ->where(
                'cart_id',
                $cartId
            )

            ->where(
                'product_id',
                $productId
            )

            ->first();

    }

    /**
     * Check product exists.
     */
    public function exists(
        int $cartId,
        int $productId
    ): bool {

        return CartItem::query()

            ->where(
                'cart_id',
                $cartId
            )

            ->where(
                'product_id',
                $productId
            )

            ->exists();

    }

    /**
     * Create item.
     */
    public function create(
        array $data
    ): CartItem {

        return CartItem::query()

            ->create(
                $data
            );

    }

    /**
     * Update item.
     */
    public function update(
        int $id,
        array $data
    ): bool {

        return $this->find($id)

            ->update(
                $data
            );

    }

    /**
     * Delete item.
     */
    public function delete(
        int $id
    ): bool {

        return $this->find($id)

            ->delete();

    }

    /**
     * Clear cart.
     */
    public function clear(
        int $cartId
    ): bool {

        return (bool) CartItem::query()

            ->where(
                'cart_id',
                $cartId
            )

            ->delete();

    }

    /**
     * Count items.
     */
    public function count(
        int $cartId
    ): int {

        return CartItem::query()

            ->where(
                'cart_id',
                $cartId
            )

            ->count();

    }
}
