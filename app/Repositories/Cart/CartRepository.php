<?php

declare(strict_types=1);

namespace App\Repositories\Cart;

use App\Models\Cart\Cart;
use App\Repositories\Contracts\CartRepositoryInterface;

class CartRepository implements CartRepositoryInterface
{
    /**
     * Get customer's active cart.
     */
    public function getActiveCart(
        int $customerId
    ): ?Cart {

        return Cart::query()

            ->with([
                'items',
                'items.product',
                'coupon',
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
     * Find cart.
     */
    public function find(
        int $id
    ): Cart {

        return Cart::query()

            ->with([
                'items',
                'items.product',
                'coupon',
            ])

            ->findOrFail(
                $id
            );

    }

    /**
     * Create cart.
     */
    public function create(
        array $data
    ): Cart {

        return Cart::query()->create(
            $data
        );

    }

    /**
     * Update cart.
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
     * Delete cart.
     */
    public function delete(
        int $id
    ): bool {

        return $this->find($id)

            ->delete();

    }
}
