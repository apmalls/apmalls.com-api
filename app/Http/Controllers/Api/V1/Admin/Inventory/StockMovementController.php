<?php

namespace App\Http\Controllers\Api\V1\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\StockMovementResource;
use App\Services\Contracts\StockMovementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(
        protected StockMovementServiceInterface $movementService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $movements = $this->movementService->getAll(
            $request->all()
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock movement list fetched successfully.',
            'data' => StockMovementResource::collection($movements),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $movement = $this->movementService->findById($id);

        if (!$movement) {
            return response()->json([
                'success' => false,
                'message' => 'Stock movement not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new StockMovementResource($movement),
        ]);
    }
}
