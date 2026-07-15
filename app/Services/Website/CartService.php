<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\CartItemRepositoryInterface;

class CartService
{
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        protected CartItemRepositoryInterface $cartItemRepository,
        protected ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * Customer Active Cart
     */
    public function index(
        int $customerId
    ): ?Cart {

        return $this->cartRepository
            ->active($customerId);

    }

    /**
     * Get Or Create Active Cart
     */
    private function activeCart(
        int $customerId
    ): Cart {

        $cart = $this->cartRepository
            ->active($customerId);

        if ($cart) {

            return $cart;

        }

        return $this->cartRepository
            ->create([

                'cart_no' => 'CRT-' . now()->format('YmdHis') . '-' . $customerId,

                'customer_id' => $customerId,

                'subtotal' => 0,

                'discount_amount' => 0,

                'tax_amount' => 0,

                'shipping_charge' => 0,

                'grand_total' => 0,

                'status' => 'Active',

            ]);

    }

    /**
     * Add Product To Cart
     */
    public function add(
        int $customerId,
        array $data
    ): Cart {

        return DB::transaction(function () use ($customerId, $data) {

            $product = $this->productRepository
                ->find($data['product_id']);

            $cart = $this->activeCart(
                $customerId
            );

            $item = $this->cartItemRepository
                ->findByProduct(
                    $cart->id,
                    $product->id
                );

            if ($item) {

                $quantity = $item->quantity + $data['quantity'];

                $this->cartItemRepository
                    ->update(
                        $item->id,
                        [

                            'quantity' => $quantity,

                            'price' => $product->selling_price,

                            'tax_percent' => $product->tax_percent,

                            'discount_percent' => $product->discount_percent,

                            'subtotal' => $quantity * $product->selling_price,

                        ]
                    );

            } else {

                $this->cartItemRepository
                    ->create([

                        'cart_id' => $cart->id,

                        'product_id' => $product->id,

                        'quantity' => $data['quantity'],

                        'price' => $product->selling_price,

                        'tax_percent' => $product->tax_percent,

                        'discount_percent' => $product->discount_percent,

                        'tax_amount' => 0,

                        'discount_amount' => 0,

                        'subtotal' => $data['quantity']
                            * $product->selling_price,

                    ]);

            }

            return $this->recalculateCart(
                $cart->id
            );

        });

    }

    /**
     * Update Cart Item
     */
    public function updateItem(
        int $itemId,
        array $data
    ): Cart {

        return DB::transaction(function () use ($itemId, $data) {

            $item = $this->cartItemRepository
                ->find($itemId);

            $this->cartItemRepository
                ->update(
                    $itemId,
                    [

                        'quantity' => $data['quantity'],

                        'subtotal' => $data['quantity']
                            * $item->price,

                    ]
                );

            return $this->recalculateCart(
                $item->cart_id
            );

        });

    }

    /**
     * Remove Cart Item
     */
    public function removeItem(
        int $itemId
    ): Cart {

        return DB::transaction(function () use ($itemId) {

            $item = $this->cartItemRepository
                ->find($itemId);

            $cartId = $item->cart_id;

            $this->cartItemRepository
                ->delete($itemId);

            return $this->recalculateCart(
                $cartId
            );

        });

    }

    /**
     * Clear Cart
     */
    public function clear(
        int $customerId
    ): bool {

        $cart = $this->cartRepository
            ->active($customerId);

        if (!$cart) {

            return true;

        }

        return $this->cartItemRepository
            ->clear($cart->id);

    }

    /**
     * Recalculate Cart
     */
    private function recalculateCart(
        int $cartId
    ): Cart {

        $items = $this->cartItemRepository
            ->items($cartId);

        $subtotal = $items->sum('subtotal');

        $discount = $items->sum(
            'discount_amount'
        );

        $tax = $items->sum(
            'tax_amount'
        );

        $shipping = 0;

        $grandTotal =

            $subtotal

            - $discount

            + $tax

            + $shipping;

        return $this->cartRepository
            ->update(
                $cartId,
                [

                    'subtotal' => $subtotal,

                    'discount_amount' => $discount,

                    'tax_amount' => $tax,

                    'shipping_charge' => $shipping,

                    'grand_total' => $grandTotal,

                ]
            );

    }


    /**
     * Cart Summary
     */
    public function summary(
        int $customerId
    ): ?Cart {

        return $this->cartRepository
            ->active($customerId);

    }

}
