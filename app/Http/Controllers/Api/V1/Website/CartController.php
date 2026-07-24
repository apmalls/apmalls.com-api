<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use App\Http\Requests\Website\Cart\ApplyCouponRequest;
use App\Http\Requests\Website\Cart\StoreCartRequest;
use App\Http\Requests\Website\Cart\UpdateCartItemRequest;
use App\Http\Resources\Website\CartResource;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

use App\Services\Contracts\CartServiceInterface;


class CartController extends Controller
{
    /**
     * Create new controller instance.
     */
    public function __construct(
        protected CartServiceInterface $cartService,
    ) {
    }

    /**
     * Customer active cart.
     */
    public function index(): JsonResponse
    {
        $cart = $this->cartService->getActiveCart(
            auth('customer')->id()
        );

        return response()->json([

            'success' => true,

            'message' => 'Cart fetched successfully.',

            'data' => $cart
                ? new CartResource($cart)
                : null,

        ]);
    }

    /**
     * Add product to cart.
     */
    public function store(
        StoreCartRequest $request
    ): JsonResponse {

        $cart = $this->cartService->addItem(

            auth('customer')->id(),

            $request->integer('product_id'),

            $request->integer('quantity')

        );

        return response()->json([

            'success' => true,

            'message' => 'Product added successfully.',

            'data' => new CartResource($cart),

        ]);

    }

    /**
     * Update cart quantity.
     */
    public function update(
        UpdateCartItemRequest $request,
        int $productId
    ): JsonResponse {

        $cart = $this->cartService->updateQuantity(

            auth('customer')->id(),

            $productId,

            $request->integer('quantity')

        );

        return response()->json([

            'success' => true,

            'message' => 'Cart updated successfully.',

            'data' => new CartResource($cart),

        ]);

    }

    /**
     * Remove cart item.
     */
    public function destroy(
        int $productId
    ): JsonResponse {

        $cart = $this->cartService->removeItem(

            auth('customer')->id(),

            $productId

        );

        return response()->json([

            'success' => true,

            'message' => 'Item removed successfully.',

            'data' => new CartResource($cart),

        ]);

    }

    /**
     * Clear customer cart.
     */
    public function clear(): JsonResponse
    {
        $this->cartService->clear(
            auth('customer')->id()
        );

        return response()->json([

            'success' => true,

            'message' => 'Cart cleared successfully.',

        ]);
    }

    /**
     * Cart item count.
     */
    public function count(): JsonResponse
    {
        return response()->json([

            'success' => true,

            'count' => $this->cartService->count(
                auth('customer')->id()
            ),

        ]);
    }

    /**
     * Apply coupon.
     */
    public function applyCoupon(
        ApplyCouponRequest $request
    ): JsonResponse {

        $cart = $this->cartService->applyCoupon(

            auth('customer')->id(),

            $request->string('coupon_code')->toString()

        );

        return response()->json([

            'success' => true,

            'message' => 'Coupon applied successfully.',

            'data' => new CartResource($cart),

        ]);

    }

    /**
     * Remove coupon.
     */
    public function removeCoupon(): JsonResponse
    {
        $cart = $this->cartService->removeCoupon(
            auth('customer')->id()
        );

        return response()->json([

            'success' => true,

            'message' => 'Coupon removed successfully.',

            'data' => new CartResource($cart),

        ]);
    }
}
