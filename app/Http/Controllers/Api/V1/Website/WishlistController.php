<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\WishlistService;
use App\Http\Requests\Website\Wishlist\AddToWishlistRequest;

class WishlistController extends Controller
{
    public function __construct(
        protected WishlistService $wishlistService,
    ) {
    }

    /**
     * Customer Wishlist
     */
    public function index(): JsonResponse
    {
        try {

            return response()->json([

                'success' => true,

                'message' => 'Wishlist fetched successfully.',

                'data' => $this->wishlistService->index(
                    auth()->user()->customer->id
                ),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Add Product To Wishlist
     */
    public function store(
        AddToWishlistRequest $request
    ): JsonResponse {

        try {

            return response()->json([

                'success' => true,

                'message' => 'Product added to wishlist successfully.',

                'data' => $this->wishlistService->add(

                    auth()->user()->customer->id,

                    $request->validated()

                ),

            ], 201);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /**
     * Remove Wishlist Item
     */
    public function destroy(
        int $wishlist
    ): JsonResponse {

        try {

            $this->wishlistService
                ->remove($wishlist);

            return response()->json([

                'success' => true,

                'message' => 'Wishlist item removed successfully.',

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Clear Wishlist
     */
    public function clear(): JsonResponse
    {
        try {

            $this->wishlistService
                ->clear(
                    auth()->user()->customer->id
                );

            return response()->json([

                'success' => true,

                'message' => 'Wishlist cleared successfully.',

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Wishlist Count
     */
    public function count(): JsonResponse
    {
        try {

            return response()->json([

                'success' => true,

                'message' => 'Wishlist count fetched successfully.',

                'data' => [

                    'count' => $this->wishlistService
                        ->count(auth()->user()->customer->id),

                ],

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }
}
