<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\BrandService;
use App\Http\Requests\Website\Brand\BrandListRequest;

class BrandController extends Controller
{
    public function __construct(
        protected BrandService $brandService,
    ) {
    }

    /**
     * Brand Listing
     */
    public function index(
        BrandListRequest $request
    ): JsonResponse {

        try {

            return response()->json([

                'success' => true,

                'message' => 'Brands fetched successfully.',

                'data' => $this->brandService->paginate(
                    $request->filters()
                ),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /**
     * Brand Details
     */
    public function show(
        string $slug
    ): JsonResponse {

        try {

            return response()->json([

                'success' => true,

                'message' => 'Brand fetched successfully.',

                'data' => $this->brandService->show($slug),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    /**
     * Active Brands
     */
    public function all(): JsonResponse
    {
        try {

            return response()->json([

                'success' => true,

                'message' => 'Brands fetched successfully.',

                'data' => $this->brandService->all(),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }
}
