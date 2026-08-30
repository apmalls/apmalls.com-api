<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Cart\Cart;
use App\Models\Product\Product;
use App\Services\Contracts\CartServiceInterface;
use Illuminate\Support\Facades\DB;

use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\Contracts\CartItemRepositoryInterface;

class CartService implements CartServiceInterface
{
    /**
     * Create new service instance.
     */
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        protected CartItemRepositoryInterface $cartItemRepository,
        protected ProductRepositoryInterface $productRepository,
        protected CouponRepositoryInterface $couponRepository,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    /**
     * Customer active cart.
     */
    public function getActiveCart(
        int $customerId
    ): ?Cart {

        $cart = $this->cartRepository->getActiveCart(
            $customerId
        );

        return $cart
            ? $this->recalculate($cart->id)
            : null;

    }

    /**
     * Create customer cart.
     */
    protected function createCart(
        int $customerId
    ): Cart {

        return $this->cartRepository->create([

            'cart_no' => $this->generateCartNumber(),

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
     * Generate cart number.
     */
    protected function generateCartNumber(): string
    {
        return 'CRT-'
            . now()->format('YmdHis')
            . random_int(1000, 9999);
    }

    /**
     * Find active cart or create.
     */
    protected function findOrCreateCart(
        int $customerId
    ): Cart {

        return $this->getActiveCart(
            $customerId
        ) ?? $this->createCart(
                    $customerId
                );

    }

    /**
     * Recalculate cart totals.
     */
    protected function recalculate(
        int $cartId
    ): Cart {

        $cart = $this->cartRepository->find(
            $cartId
        );

        $subtotal = 0;

        $discount = 0;

        $tax = 0;

        foreach ($cart->items as $item) {

            $subtotal +=
                (float) $item->price
                *
                (int) $item->quantity;

            $discount += $item->discount_amount;

            $tax += $item->tax_amount;

        }

        $shipping = $cart->shipping_charge;

        $couponDiscount = 0;

        $discountableSubtotal = max(
            0,
            $subtotal - $discount
        );

        if ($cart->coupon) {

            if ($cart->coupon->discount_type === 'Fixed') {

                $couponDiscount = min(
                    (float) $cart->coupon->discount_value,
                    $discountableSubtotal
                );

            } else {

                $couponDiscount = (
                    $discountableSubtotal
                    *
                    $cart->coupon->discount_value
                ) / 100;

                if (

                    $cart->coupon->maximum_discount_amount

                    &&

                    $couponDiscount >

                    $cart->coupon->maximum_discount_amount

                ) {

                    $couponDiscount =
                        $cart->coupon->maximum_discount_amount;

                }

            }

        }

        $grandTotal =

            $subtotal

            -

            $discount

            +

            $tax

            +

            $shipping

            -

            $couponDiscount;

        $totals = [

            'subtotal' => round($subtotal, 2),

            'discount_amount' => round(
                $discount + $couponDiscount,
                2
            ),

            'tax_amount' => round($tax, 2),

            'grand_total' => round(
                max(0, $grandTotal),
                2
            ),

        ];

        $totalsChanged =
            round((float) $cart->subtotal, 2) !== $totals['subtotal']
            ||
            round((float) $cart->discount_amount, 2) !== $totals['discount_amount']
            ||
            round((float) $cart->tax_amount, 2) !== $totals['tax_amount']
            ||
            round((float) $cart->grand_total, 2) !== $totals['grand_total'];

        if (!$totalsChanged) {

            return $cart;

        }

        $this->cartRepository->update(

            $cart->id,

            $totals

        );

        return $this->cartRepository->find(
            $cart->id
        );

    }

    /**
     * Add product to cart.
     */
    public function addItem(
        int $customerId,
        int $productId,
        int $quantity = 1
    ): Cart {

        return DB::transaction(function () use ($customerId, $productId, $quantity) {

            $cart = $this->findOrCreateCart(
                $customerId
            );

            $product = $this->productRepository->find(
                $productId
            );

            if (!$product->is_active) {

                throw new \Exception(
                    'Product is inactive.'
                );

            }

            if ($product->stock < $quantity) {

                throw new \Exception(
                    'Insufficient stock.'
                );

            }

            $cartItem = $this->cartItemRepository
                ->findByProduct(
                    $cart->id,
                    $productId
                );

            if ($cartItem) {

                $newQuantity =

                    $cartItem->quantity

                    +

                    $quantity;

                if ($product->stock < $newQuantity) {

                    throw new \Exception(
                        'Insufficient stock.'
                    );

                }

                $price = (float) $product->selling_price;

                $discountAmount =

                    ($price * $product->discount_percent)

                    / 100;

                $priceAfterDiscount =

                    $price

                    -

                    $discountAmount;

                $taxAmount =

                    (

                        $priceAfterDiscount

                        *

                        $product->tax_percent

                    )

                    / 100;

                $lineTotal =

                    (

                        $priceAfterDiscount

                        +

                        $taxAmount

                    )

                    *

                    $newQuantity;

                $this->cartItemRepository->update(

                    $cartItem->id,

                    [

                        'quantity' => $newQuantity,

                        'price' => $price,

                        'tax_percent' => $product->tax_percent,

                        'tax_amount' =>

                            $taxAmount

                            *

                            $newQuantity,

                        'discount_percent' =>

                            $product->discount_percent,

                        'discount_amount' =>

                            $discountAmount

                            *

                            $newQuantity,

                        'subtotal' => $lineTotal,

                    ]

                );

            } else {

                $price = (float) $product->selling_price;

                $discountAmount =

                    ($price * $product->discount_percent)

                    / 100;

                $priceAfterDiscount =

                    $price

                    -

                    $discountAmount;

                $taxAmount =

                    (

                        $priceAfterDiscount

                        *

                        $product->tax_percent

                    )

                    / 100;

                $lineTotal =

                    (

                        $priceAfterDiscount

                        +

                        $taxAmount

                    )

                    *

                    $quantity;

                $this->cartItemRepository->create([

                    'cart_id' => $cart->id,

                    'product_id' => $product->id,

                    'quantity' => $quantity,

                    'price' => $price,

                    'tax_percent' => $product->tax_percent,

                    'tax_amount' =>

                        $taxAmount

                        *

                        $quantity,

                    'discount_percent' =>

                        $product->discount_percent,

                    'discount_amount' =>

                        $discountAmount

                        *

                        $quantity,

                    'subtotal' => $lineTotal,

                ]);

            }

            return $this->recalculate(
                $cart->id
            );

        });

    }

    /**
     * Update cart item quantity.
     */
    public function updateQuantity(
        int $customerId,
        int $productId,
        int $quantity
    ): Cart {

        return DB::transaction(function () use ($customerId, $productId, $quantity) {

            if ($quantity < 1) {

                throw new \Exception(
                    'Quantity must be at least 1.'
                );

            }

            $cart = $this->getActiveCart(
                $customerId
            );

            if (!$cart) {
                throw new \Exception(
                    'Cart not found.'
                );

            }

            $cartItem = $this->cartItemRepository
                ->findByProduct(
                    $cart->id,
                    $productId
                );

            if (!$cartItem) {

                throw new \Exception(
                    'Product not found in cart.'
                );

            }

            $product = $this->productRepository->find(
                $productId
            );

            if ($product->stock < $quantity) {

                throw new \Exception(
                    'Insufficient stock.'
                );

            }

            $price = (float) $product->selling_price;

            $discountAmount =

                ($price * $product->discount_percent)

                / 100;

            $priceAfterDiscount =

                $price

                -

                $discountAmount;

            $taxAmount =

                (

                    $priceAfterDiscount

                    *

                    $product->tax_percent

                )

                / 100;

            $lineTotal =

                (

                    $priceAfterDiscount

                    +

                    $taxAmount

                )

                *

                $quantity;

            $this->cartItemRepository->update(

                $cartItem->id,

                [

                    'quantity' => $quantity,

                    'price' => $price,

                    'tax_percent' => $product->tax_percent,

                    'tax_amount' =>

                        $taxAmount

                        *

                        $quantity,

                    'discount_percent' =>

                        $product->discount_percent,

                    'discount_amount' =>

                        $discountAmount

                        *

                        $quantity,

                    'subtotal' => $lineTotal,

                ]

            );

            return $this->recalculate(
                $cart->id
            );

        });

    }

    /**
     * Remove cart item.
     */
    public function removeItem(
        int $customerId,
        int $productId
    ): Cart {

        return DB::transaction(function () use ($customerId, $productId) {

            $cart = $this->getActiveCart(
                $customerId
            );

            if (!$cart) {

                throw new \Exception(
                    'Cart not found.'
                );

            }

            $cartItem = $this->cartItemRepository
                ->findByProduct(
                    $cart->id,
                    $productId
                );

            if (!$cartItem) {

                throw new \Exception(
                    'Product not found in cart.'
                );

            }

            $this->cartItemRepository->delete(
                $cartItem->id
            );

            return $this->recalculate(
                $cart->id
            );

        });

    }

    /**
     * Clear customer cart.
     */
    public function clear(
        int $customerId
    ): bool {

        return DB::transaction(function () use ($customerId) {

            $cart = $this->getActiveCart(
                $customerId
            );

            if (!$cart) {

                return true;

            }

            $this->cartItemRepository->clear(
                $cart->id
            );

            $this->cartRepository->update(

                $cart->id,

                [

                    'coupon_id' => null,

                    'subtotal' => 0,

                    'discount_amount' => 0,

                    'tax_amount' => 0,

                    'shipping_charge' => 0,

                    'grand_total' => 0,

                ]

            );

            return true;

        });

    }

    /**
     * Cart item count.
     */
    public function count(
        int $customerId
    ): int {

        $cart = $this->getActiveCart(
            $customerId
        );

        if (!$cart) {

            return 0;

        }

        return $this->cartItemRepository->count(
            $cart->id
        );

    }

    /**
     * Apply coupon.
     */
    public function applyCoupon(
        int $customerId,
        string $couponCode
    ): Cart {

        return DB::transaction(function () use ($customerId, $couponCode) {

            $cart = $this->getActiveCart(
                $customerId
            );

            if (!$cart) {

                throw new \Exception(
                    'Cart not found.'
                );

            }

            $coupon = $this->couponRepository->findByCode(
                $couponCode
            );

            if (!$coupon) {

                throw new \Exception(
                    'Invalid coupon.'
                );

            }

            if (

                $coupon->start_at

                &&

                now()->lt(
                    $coupon->start_at
                )

            ) {

                throw new \Exception(
                    'Coupon is not active yet.'
                );

            }

            if (

                $coupon->end_at

                &&

                now()->gt(
                    $coupon->end_at
                )

            ) {

                throw new \Exception(
                    'Coupon has expired.'
                );

            }

            if (

                $coupon->usage_limit

                &&

                $coupon->used_count >=

                $coupon->usage_limit

            ) {

                throw new \Exception(
                    'Coupon usage limit exceeded.'
                );

            }

            if (

                $cart->subtotal <

                $coupon->minimum_order_amount

            ) {

                throw new \Exception(
                    'Minimum order amount not reached.'
                );

            }

            $this->cartRepository->update(

                $cart->id,

                [

                    'coupon_id' => $coupon->id,

                ]

            );

            $this->couponRepository->incrementUsage(
                $coupon->id
            );

            return $this->recalculate(
                $cart->id
            );

        });

    }

    /**
     * Remove coupon.
     */
    public function removeCoupon(
        int $customerId
    ): Cart {

        return DB::transaction(function () use ($customerId) {

            $cart = $this->getActiveCart(
                $customerId
            );

            if (!$cart) {

                throw new \Exception(
                    'Cart not found.'
                );

            }

            if (

                $cart->coupon_id

            ) {

                $this->couponRepository
                    ->decrementUsage(
                        $cart->coupon_id
                    );

            }

            $this->cartRepository->update(

                $cart->id,

                [

                    'coupon_id' => null,

                ]

            );

            return $this->recalculate(
                $cart->id
            );

        });

    }

}
