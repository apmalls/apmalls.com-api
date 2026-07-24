<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Brand\BrandResource;
use App\Http\Resources\Product\ProductResource;
use App\Services\Contracts\BrandServiceInterface;
use App\Http\Requests\Website\Brand\BrandListRequest;

class BrandController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected BrandServiceInterface $brandService
    ) {}

    /**
     * Brand listing.
     */
    public function index(
        BrandListRequest $request
    ): JsonResponse {

        try {

            $brands = $this->brandService->websitePaginate(
                $request->filters()
            );

            return response()->json([

                'success' => true,

                'message' => 'Brands fetched successfully.',

                'data' => BrandResource::collection($brands),

                'pagination' => [

                    'current_page' => $brands->currentPage(),

                    'last_page' => $brands->lastPage(),

                    'per_page' => $brands->perPage(),

                    'total' => $brands->total(),

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
     * Brand details.
     */
    public function show(
        string $slug
    ): JsonResponse {

        try {

            $brand = $this->brandService->findBySlug($slug);

            return response()->json([

                'success' => true,

                'message' => 'Brand fetched successfully.',

                'data' => [

                    'brand' => new BrandResource($brand),

                    'products' => ProductResource::collection(
                        $brand->products
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
