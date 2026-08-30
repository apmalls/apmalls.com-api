<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use App\Http\Requests\Website\Category\CategoryListRequest;
use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\Product\ProductResource;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

use App\Services\Contracts\CategoryServiceInterface;


class CategoryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected CategoryServiceInterface $categoryService
    ) {}

    /**
     * Category listing.
     */
    public function index(
        CategoryListRequest $request
    ): JsonResponse {

        try {

            $categories = $this->categoryService->websitePaginate(
                $request->filters()
            );

            return response()->json([

                'success' => true,

                'message' => 'Categories fetched successfully.',

                'data' => CategoryResource::collection($categories),

                'pagination' => [

                    'current_page' => $categories->currentPage(),

                    'last_page' => $categories->lastPage(),

                    'per_page' => $categories->perPage(),

                    'total' => $categories->total(),

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
     * Active category hierarchy for website navigation.
     */
    public function tree(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Category tree fetched successfully.',
                'data' => CategoryResource::collection(
                    $this->categoryService->websiteTree()
                ),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Category details.
     */
    public function show(
        string $slug
    ): JsonResponse {

        try {

            $category = $this->categoryService->findBySlug($slug);

            return response()->json([

                'success' => true,

                'message' => 'Category fetched successfully.',

                'data' => [

                    'category' => new CategoryResource($category),

                    'products' => ProductResource::collection(
                        $category->products
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
}
