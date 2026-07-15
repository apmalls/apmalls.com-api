<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\CartService;
use App\Http\Requests\Website\Cart\AddToCartRequest;
use App\Http\Requests\Website\Cart\UpdateCartItemRequest;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
    ) {
    }

    /**
     * Customer Cart
     */
    public function index(): JsonResponse
    {
        try {

            return response()->json([

                'success' => true,

                'message' => 'Cart fetched successfully.',

                'data' => $this->cartService->index(
                    auth()->id()
                ),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Add Item
     */
    public function store(
        AddToCartRequest $request
    ): JsonResponse {

        try {

            return response()->json([

                'success' => true,

                'message' => 'Product added to cart successfully.',

                'data' => $this->cartService->add(

                    auth()->id(),

                    $request->validated()

                ),

            ], 201);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /**
     * Update Cart Item
     */
    public function update(
        UpdateCartItemRequest $request,
        int $item
    ): JsonResponse {

        try {

            return response()->json([

                'success' => true,

                'message' => 'Cart updated successfully.',

                'data' => $this->cartService->updateItem(

                    $item,

                    $request->validated()

                ),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /**
     * Remove Cart Item
     */
    public function destroy(
        int $item
    ): JsonResponse {

        try {

            return response()->json([

                'success' => true,

                'message' => 'Cart item removed successfully.',

                'data' => $this->cartService->removeItem(
                    $item
                ),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /**
     * Clear Cart
     */
    public function clear(): JsonResponse
    {
        try {

            $this->cartService->clear(
                auth()->id()
            );

            return response()->json([

                'success' => true,

                'message' => 'Cart cleared successfully.',

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Cart Summary
     */
    public function summary(): JsonResponse
    {
        try {

            return response()->json([

                'success' => true,

                'message' => 'Cart summary fetched successfully.',

                'data' => $this->cartService->summary(
                    auth()->id()
                ),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }
}
