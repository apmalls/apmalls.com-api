<?php

declare(strict_types=1);

namespace App\Repositories\Cart;

use App\Models\Cart\Cart;
use App\Repositories\Contracts\CartRepositoryInterface;

class CartRepository implements CartRepositoryInterface
{
    /**
     * Get Active Cart
     */
    public function active(
        int $customerId
    ): ?Cart {

        return Cart::query()

            ->with([
                'items',
                'items.product',
                'items.product.images',
                'items.product.brand',
                'items.product.category',
            ])

            ->where(
                'customer_id',
                $customerId
            )

            ->where(
                'status',
                'Active'
            )

            ->first();

    }

    /**
     * Find Cart
     */
    public function find(
        int $id
    ): Cart {

        return Cart::query()

            ->with([
                'items',
                'items.product',
                'items.product.images',
                'items.product.brand',
                'items.product.category',
            ])

            ->findOrFail($id);

    }

    /**
     * Create Cart
     */
    public function create(
        array $data
    ): Cart {

        return Cart::create($data);

    }

    /**
     * Update Cart
     */
    public function update(
        int $id,
        array $data
    ): Cart {

        $cart = $this->find($id);

        $cart->update($data);

        return $cart->refresh();

    }

    /**
     * Delete Cart
     */
    public function delete(
        int $id
    ): bool {

        return (bool) $this->find($id)

            ->delete();

    }
}
