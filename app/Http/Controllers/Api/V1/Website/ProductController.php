<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\ProductService;
use App\Http\Requests\Website\Product\ProductListRequest;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Product Listing
    |--------------------------------------------------------------------------
    */

    /**
     * Display product listing.
     */
    public function index(
        ProductListRequest $request
    ): JsonResponse {

        try {

            $products = $this->productService
                ->paginate(
                    $request->filters()
                );

            return response()->json([

                'success' => true,

                'message' => 'Products fetched successfully.',

                'data' => $products,

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Product Details
    |--------------------------------------------------------------------------
    */

    /**
     * Display product details.
     */
    public function show(
        string $slug
    ): JsonResponse {

        try {

            $product = $this->productService
                ->show($slug);

            return response()->json([

                'success' => true,

                'message' => 'Product fetched successfully.',

                'data' => $product,

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Featured Products
    |--------------------------------------------------------------------------
    */

    /**
     * Display featured products.
     */
    public function featured(): JsonResponse
    {
        try {

            $products = $this->productService
                ->featured();

            return response()->json([

                'success' => true,

                'message' => 'Featured products fetched successfully.',

                'data' => $products,

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | New Arrival Products
    |--------------------------------------------------------------------------
    */

    /**
     * Display new arrival products.
     */
    public function newArrivals(): JsonResponse
    {
        try {

            $products = $this->productService
                ->newArrivals();

            return response()->json([

                'success' => true,

                'message' => 'New arrival products fetched successfully.',

                'data' => $products,

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Best Seller Products
    |--------------------------------------------------------------------------
    */

    /**
     * Display best seller products.
     */
    public function bestSellers(): JsonResponse
    {
        try {

            $products = $this->productService
                ->bestSellers();

            return response()->json([

                'success' => true,

                'message' => 'Best seller products fetched successfully.',

                'data' => $products,

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Related Products
    |--------------------------------------------------------------------------
    */

    /**
     * Display related products.
     */
    public function related(
        string $slug
    ): JsonResponse {

        try {

            $products = $this->productService
                ->related($slug);

            return response()->json([

                'success' => true,

                'message' => 'Related products fetched successfully.',

                'data' => $products,

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Product Search
    |--------------------------------------------------------------------------
    */

    /**
     * Search products.
     */
    public function search(
        ProductListRequest $request
    ): JsonResponse {

        try {

            $products = $this->productService
                ->search(
                    $request->filters()
                );

            return response()->json([

                'success' => true,

                'message' => 'Products fetched successfully.',

                'data' => $products,

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }
}
