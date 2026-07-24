<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use App\Http\Requests\Website\Wishlist\StoreWishlistRequest;
use App\Http\Resources\Website\WishlistResource;
use App\Services\Contracts\WishlistServiceInterface;
use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\WishlistService;
use App\Http\Requests\Website\Wishlist\AddToWishlistRequest;

class WishlistController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected WishlistServiceInterface $wishlistService,
    ) {}

    /**
     * Customer wishlist.
     */
    public function index(): JsonResponse
    {
        $customerId = auth('customer')->id();

        $wishlists = $this->wishlistService->index(
            $customerId
        );

        return response()->json([
            'success' => true,
            'message' => 'Wishlist fetched successfully.',
            'data'    => WishlistResource::collection($wishlists),
        ]);
    }

    /**
     * Add product to wishlist.
     */
    public function store(
        StoreWishlistRequest $request
    ): JsonResponse {

        $customerId = auth('customer')->id();

        if (
            $this->wishlistService->exists(
                $customerId,
                $request->integer('product_id')
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Product already exists in wishlist.',
            ], 422);
        }

        $wishlist = $this->wishlistService->create([
            'customer_id' => $customerId,
            'product_id'  => $request->integer('product_id'),
            'remarks'     => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to wishlist successfully.',
            'data'    => new WishlistResource($wishlist),
        ], 201);
    }

    /**
     * Remove wishlist item.
     */
    public function destroy(
        int $productId
    ): JsonResponse {

        $customerId = auth('customer')->id();

        $this->wishlistService->delete(
            $customerId,
            $productId
        );

        return response()->json([
            'success' => true,
            'message' => 'Product removed from wishlist successfully.',
        ]);
    }

    /**
     * Clear customer wishlist.
     */
    public function clear(): JsonResponse
    {
        $customerId = auth('customer')->id();

        $this->wishlistService->clear(
            $customerId
        );

        return response()->json([
            'success' => true,
            'message' => 'Wishlist cleared successfully.',
        ]);
    }

    /**
     * Wishlist count.
     */
    public function count(): JsonResponse
    {
        $customerId = auth('customer')->id();

        return response()->json([
            'success' => true,
            'count'   => $this->wishlistService->count(
                $customerId
            ),
        ]);
    }

    /**
     * Check wishlist status.
     */
    public function check(
        int $productId
    ): JsonResponse {

        $customerId = auth('customer')->id();

        return response()->json([
            'success' => true,
            'exists'  => $this->wishlistService->exists(
                $customerId,
                $productId
            ),
        ]);
    }
}
