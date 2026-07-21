<?php

declare(strict_types=1);

namespace App\Repositories\Cart;

use App\Models\Cart\CartItem;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\CartItemRepositoryInterface;

class CartItemRepository implements CartItemRepositoryInterface
{
    /**
     * Cart Items
     */
    public function items(
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
     * Find Product In Cart
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
     * Add Item
     */
    public function create(
        array $data
    ): CartItem {

        return CartItem::create($data);

    }

    /**
     * Update Item
     */
    public function update(
        int $id,
        array $data
    ): CartItem {

        $item = $this->find($id);

        $item->update($data);

        return $item->refresh();

    }

    /**
     * Delete Item
     */
    public function delete(
        int $id
    ): bool {

        return (bool) $this->find($id)

            ->delete();

    }

    /**
     * Clear Cart
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
     * Total Quantity
     */
    public function totalQuantity(
        int $cartId
    ): int {

        return (int) CartItem::query()

            ->where(
                'cart_id',
                $cartId
            )

            ->sum('quantity');

    }

    /**
     * Cart Subtotal
     */
    public function subtotal(
        int $cartId
    ): float {

        return (float) CartItem::query()

            ->where(
                'cart_id',
                $cartId
            )

            ->sum('subtotal');

    }
}
