<?php

namespace App\Http\Controllers\Api\V1\Admin\Purchase;

use App\Http\Controllers\Controller;

use App\Http\Requests\Admin\Purchase\StorePurchaseOrderRequest;
use App\Http\Requests\Admin\Purchase\UpdatePurchaseOrderRequest;
use App\Http\Resources\Purchase\PurchaseCollection;
use App\Http\Resources\Purchase\PurchaseResource;
use App\Services\Contracts\PurchaseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseServiceInterface $purchaseService
    ) {
    }

    /**
     * Purchase Listing
     */
    public function index(Request $request): PurchaseCollection
    {
        $purchases = $this->purchaseService->paginate(
            $request->all(),
            $request->get('per_page', 15)
        );

        return new PurchaseCollection($purchases);
    }

    /**
     * All Purchases
     */
    public function all(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Purchase list retrieved successfully.',
            'data' => PurchaseResource::collection(
                $this->purchaseService->all()
            ),
        ]);
    }

    /**
     * Trashed Purchases
     */
    public function trashed(Request $request): PurchaseCollection
    {
        $purchases = $this->purchaseService->trashedPaginate(
            $request->get('per_page', 15)
        );

        return new PurchaseCollection($purchases);
    }

    /**
     * Store Purchase
     */
    public function store(
        StorePurchaseOrderRequest $request
    ): JsonResponse {

        $purchase = $this->purchaseService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase created successfully.',
            'data' => new PurchaseResource($purchase),
        ], Response::HTTP_CREATED);
    }

    /**
     * Show Purchase
     */
    public function show(int $id): JsonResponse
    {
        $purchase = $this->purchaseService->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase retrieved successfully.',
            'data' => new PurchaseResource($purchase),
        ]);
    }

    /**
     * Update Purchase
     */
    public function update(
        UpdatePurchaseOrderRequest $request,
        int $id
    ): JsonResponse {

        $purchase = $this->purchaseService->update(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase updated successfully.',
            'data' => new PurchaseResource($purchase),
        ]);
    }

    /**
     * Delete Purchase
     */
    public function destroy(int $id): JsonResponse
    {
        $this->purchaseService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase deleted successfully.',
        ]);
    }

    /**
     * Restore Purchase
     */
    public function restore(int $id): JsonResponse
    {
        $this->purchaseService->restore($id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase restored successfully.',
        ]);
    }

    /**
     * Permanent Delete
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->purchaseService->forceDelete($id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase permanently deleted.',
        ]);
    }

    /**
     * Change Purchase Status
     */
    public function changeStatus(
        Request $request,
        int $id
    ): JsonResponse {

        $request->validate([
            'status' => ['required', 'string'],
        ]);

        $purchase = $this->purchaseService->changeStatus(
            $id,
            $request->status
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase status updated successfully.',
            'data' => new PurchaseResource($purchase),
        ]);
    }

    /**
     * Purchase Count
     */
    public function count(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'count' => $this->purchaseService->count(),
            ],
        ]);
    }

    /**
     * Total Purchase Amount
     */
    public function totalAmount(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_amount' => $this->purchaseService->totalAmount(),
            ],
        ]);
    }
}
