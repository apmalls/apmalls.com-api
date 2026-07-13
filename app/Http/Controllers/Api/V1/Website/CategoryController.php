<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\CategoryService;
use App\Http\Requests\Website\Category\CategoryListRequest;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Category Listing
    |--------------------------------------------------------------------------
    */

    /**
     * Display category listing.
     */
    public function index(
        CategoryListRequest $request
    ): JsonResponse {

        try {

            $categories = $this->categoryService->paginate(
                $request->filters()
            );

            return response()->json([

                'success' => true,

                'message' => 'Categories fetched successfully.',

                'data' => $categories,

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Featured Categories
    |--------------------------------------------------------------------------
    */

    /**
     * Display featured categories.
     */
    public function featured(): JsonResponse
    {
        try {

            $categories = $this->categoryService->featured();

            return response()->json([

                'success' => true,

                'message' => 'Featured categories fetched successfully.',

                'data' => $categories,

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Category Details
    |--------------------------------------------------------------------------
    */

    /**
     * Display category details.
     */
    public function show(
        string $slug
    ): JsonResponse {

        try {

            $category = $this->categoryService->show(
                $slug
            );

            return response()->json([

                'success' => true,

                'message' => 'Category fetched successfully.',

                'data' => $category,

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }
}
