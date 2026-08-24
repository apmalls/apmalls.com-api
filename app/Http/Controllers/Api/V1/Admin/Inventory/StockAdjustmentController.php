<?php

namespace App\Http\Controllers\Api\V1\Admin\Inventory;

use App\Helpers\StockHelper;
use App\Http\Controllers\Controller;


use App\Http\Requests\Admin\Inventory\StoreStockAdjustmentRequest;
use App\Http\Requests\Admin\Inventory\UpdateStockAdjustmentRequest;
use App\Http\Resources\Inventory\StockAdjustmentResource;
use App\Models\Inventory\StockAdjustment;
use App\Services\Contracts\StockAdjustmentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected StockAdjustmentServiceInterface $adjustmentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $adjustments = $this->adjustmentService->getAll(
            $request->all()
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock adjustment list fetched successfully.',
            'data' => StockAdjustmentResource::collection($adjustments),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        $stock = StockHelper::currentStock(
            $request->product_id
        );

        $adjustment = $this->adjustmentService->create([
            'product_id'     => $request->product_id,
            'system_stock'   => $stock,
            'physical_stock' => $request->physical_stock,
            'difference'     => $request->physical_stock - $stock,
            'reason'         => $request->reason,
            'created_by'     => Auth::id(),
        ]);

        StockHelper::adjust(
            $request->product_id,
            $request->physical_stock,
            StockAdjustment::class,
            $adjustment->id,
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock adjusted successfully.',
            'data' => new StockAdjustmentResource(
                $adjustment->fresh(['product', 'creator'])
            ),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $adjustment = $this->adjustmentService->findById($id);

        if (!$adjustment) {
            return response()->json([
                'success' => false,
                'message' => 'Stock adjustment not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new StockAdjustmentResource($adjustment),
        ]);
    }

    public function update(UpdateStockAdjustmentRequest $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Updating stock adjustments is not allowed.',
        ], 422);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Deleting stock adjustments is not allowed.',
        ], 422);
    }
}
