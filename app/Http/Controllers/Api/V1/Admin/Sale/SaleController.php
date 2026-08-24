<?php

namespace App\Http\Controllers\Api\V1\Admin\Sale;

use App\Http\Controllers\Controller;

use App\Http\Requests\Admin\Sale\ChangeSaleStatusRequest;
use App\Http\Requests\Admin\Sale\StoreSaleOrderRequest;
use App\Http\Requests\Admin\Sale\UpdateSaleOrderRequest;
use App\Http\Resources\Sale\SaleCollection;
use App\Http\Resources\Sale\SaleResource;
use App\Services\Contracts\SaleServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        protected SaleServiceInterface $saleService
    ) {
    }

    /**
     * Display listing.
     */
    public function index(Request $request): SaleCollection
    {
        return new SaleCollection(
            $this->saleService->paginate(
                perPage: $request->integer('per_page', 15),
                filters: $request->all()
            )
        );
    }

    /**
     * Store.
     */
    public function store(StoreSaleOrderRequest $request): SaleResource
    {
        return new SaleResource(
            $this->saleService->create(
                $request->validated()
            )
        );
    }

    /**
     * Show.
     */
    public function show(int $id): SaleResource
    {
        return new SaleResource(
            $this->saleService->findOrFail($id)
        );
    }

    /**
     * Update.
     */
    public function update(
        UpdateSaleOrderRequest $request,
        int $id
    ): SaleResource {

        return new SaleResource(
            $this->saleService->update(
                $id,
                $request->validated()
            )
        );
    }

    /**
     * Delete.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->saleService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Sale deleted successfully.',
        ]);
    }

    /**
     * Trash List.
     */
    public function trash(Request $request): SaleCollection
    {
        return new SaleCollection(
            $this->saleService->trashedPaginate(
                $request->integer('per_page', 15)
            )
        );
    }

    /**
     * Restore.
     */
    public function restore(int $id): JsonResponse
    {
        $this->saleService->restore($id);

        return response()->json([
            'success' => true,
            'message' => 'Sale restored successfully.',
        ]);
    }

    /**
     * Force Delete.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->saleService->forceDelete($id);

        return response()->json([
            'success' => true,
            'message' => 'Sale permanently deleted successfully.',
        ]);
    }

    /**
     * Change Status.
     */
    public function changeStatus(
        ChangeSaleStatusRequest $request,
        int $id
    ): SaleResource {

        return new SaleResource(
            $this->saleService->changeStatus(
                $id,
                $request->validated('status')
            )
        );
    }

    /**
     * Count.
     */
    public function count(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'count' => $this->saleService->count(
                $request->all()
            ),
        ]);
    }

    /**
     * Total Amount.
     */
    public function totalAmount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'total_amount' => $this->saleService->totalAmount(
                $request->all()
            ),
        ]);
    }
}
