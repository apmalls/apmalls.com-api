<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Unit\UnitResource;
use App\Http\Resources\Product\ProductResource;
use App\Services\Contracts\UnitServiceInterface;
use App\Http\Requests\Website\Unit\UnitListRequest;

class UnitController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected UnitServiceInterface $unitService
    ) {}

    /**
     * Unit listing.
     */
    public function index(
        UnitListRequest $request
    ): JsonResponse {

        try {

            $units = $this->unitService->websitePaginate(
                $request->filters()
            );

            return response()->json([

                'success' => true,

                'message' => 'Units fetched successfully.',

                'data' => UnitResource::collection($units),

                'pagination' => [

                    'current_page' => $units->currentPage(),

                    'last_page' => $units->lastPage(),

                    'per_page' => $units->perPage(),

                    'total' => $units->total(),

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
     * Unit details.
     */
    public function show(
        string $slug
    ): JsonResponse {

        try {

            $unit = $this->unitService->findBySlug($slug);

            return response()->json([

                'success' => true,

                'message' => 'Unit fetched successfully.',

                'data' => [

                    'unit' => new UnitResource($unit),

                    'products' => ProductResource::collection(
                        $unit->products
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
