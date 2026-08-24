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
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CartController extends Controller
{
    /**
     * Create new controller instance.
     */
    public function __construct(
        protected CartServiceInterface $cartService,
    ) {}

    /**
     * Customer active cart.
     */
    public function index(): JsonResponse
    {
        $cart = $this->cartService->getActiveCart(
            $this->customerId()
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

            $this->customerId(),

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

            $this->customerId(),

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

            $this->customerId(),

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
            $this->customerId()
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
                $this->customerId()
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

            $this->customerId(),

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
            $this->customerId()
        );

        return response()->json([

            'success' => true,

            'message' => 'Coupon removed successfully.',

            'data' => new CartResource($cart),

        ]);
    }


    /**
     * Get authenticated customer id.
     */
    private function customerId(): int
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->customer) {
            abort(404, 'Customer profile not found.');
        }

        return $user->customer->id;
    }
}
