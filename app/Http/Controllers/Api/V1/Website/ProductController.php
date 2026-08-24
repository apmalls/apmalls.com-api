<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use App\Http\Requests\Website\Product\ProductListRequest;
use App\Http\Resources\Product\ProductResource;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

use App\Services\Contracts\ProductServiceInterface;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ProductServiceInterface $productService
    ) {
    }

    /**
     * Product listing.
     */
    public function index(
        ProductListRequest $request
    ): JsonResponse {

        try {

            $products = $this->productService->websitePaginate(
                $request->filters()
            );

            return response()->json([

                'success' => true,

                'message' => 'Products fetched successfully.',

                'data' => ProductResource::collection($products),

                'pagination' => [

                    'current_page' => $products->currentPage(),

                    'last_page' => $products->lastPage(),

                    'per_page' => $products->perPage(),

                    'total' => $products->total(),

                ],

            ]);

        } catch (Exception $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }

    /**
     * Product details.
     */
    public function show(
        string $slug
    ): JsonResponse {

        try {

            $product = $this->productService->findBySlug($slug);

            $relatedProducts = $this->productService->relatedProducts(
                $product->category_id,
                $product->id
            );

            return response()->json([

                'success' => true,

                'message' => 'Product fetched successfully.',

                'data' => [

                    'product' => new ProductResource($product),

                    'related_products' => ProductResource::collection(
                        $relatedProducts
                    ),

                ],

            ]);

        } catch (Exception $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }

    /**
     * Product search suggestions.
     */
    public function searchSuggestions(
        string $keyword
    ): JsonResponse {

        try {

            $products = $this->productService->searchSuggestions($keyword);

            return response()->json([

                'success' => true,

                'message' => 'Suggestions fetched successfully.',

                'data' => $products,

            ]);

        } catch (Exception $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }

    /**
     * Related products.
     */
    public function relatedProducts(
        string $slug
    ): JsonResponse {

        try {

            $product = $this->productService->findBySlug($slug);

            $relatedProducts = $this->productService->relatedProducts(
                $product->category_id,
                $product->id
            );

            return response()->json([

                'success' => true,

                'message' => 'Related products fetched successfully.',

                'data' => ProductResource::collection(
                    $relatedProducts
                ),

            ]);

        } catch (Exception $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }
}
