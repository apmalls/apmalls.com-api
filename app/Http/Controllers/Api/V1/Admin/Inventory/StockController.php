<?php

namespace App\Http\Controllers\Api\V1\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\StockResource;
use App\Services\Contracts\StockServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(
        protected StockServiceInterface $stockService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $stocks = $this->stockService->getAll(
            $request->all()
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock list fetched successfully.',
            'data' => StockResource::collection($stocks),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $stock = $this->stockService->findById($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new StockResource($stock),
        ]);
    }
}
