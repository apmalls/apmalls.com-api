<?php

namespace App\Http\Controllers\Api\V1\Admin\Sale;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Sale\ChangeSaleReturnStatusRequest;
use App\Http\Requests\Admin\Sale\StoreSaleReturnRequest;
use App\Http\Requests\Admin\Sale\UpdateSaleReturnRequest;
use App\Http\Resources\Sale\SaleReturnCollection;
use App\Http\Resources\Sale\SaleReturnResource;
use App\Services\Contracts\SaleReturnServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    public function __construct(
        protected SaleReturnServiceInterface $saleReturnService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): SaleReturnCollection {

        return new SaleReturnCollection(
            $this->saleReturnService->paginate(
                perPage: (int) $request->input('per_page', 15),
                filters: $request->all()
            )
        );
    }

    public function trash(
        Request $request
    ): SaleReturnCollection {

        return new SaleReturnCollection(
            $this->saleReturnService->trashedPaginate(
                perPage: (int) $request->input('per_page', 15)
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        int $id
    ): SaleReturnResource {

        return new SaleReturnResource(
            $this->saleReturnService->findOrFail($id)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreSaleReturnRequest $request
    ): SaleReturnResource {

        return new SaleReturnResource(
            $this->saleReturnService->create(
                $request->validated()
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateSaleReturnRequest $request,
        int $id
    ): SaleReturnResource {

        return new SaleReturnResource(
            $this->saleReturnService->update(
                $id,
                $request->validated()
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(
        int $id
    ): JsonResponse {

        $this->saleReturnService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Sale return deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Restore
    |--------------------------------------------------------------------------
    */

    public function restore(
        int $id
    ): JsonResponse {

        $this->saleReturnService->restore($id);

        return response()->json([
            'success' => true,
            'message' => 'Sale return restored successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Force Delete
    |--------------------------------------------------------------------------
    */

    public function forceDelete(
        int $id
    ): JsonResponse {

        $this->saleReturnService->forceDelete($id);

        return response()->json([
            'success' => true,
            'message' => 'Sale return permanently deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        ChangeSaleReturnStatusRequest $request,
        int $id
    ): SaleReturnResource {

        return new SaleReturnResource(
            $this->saleReturnService->changeStatus(
                $id,
                $request->validated()['status']
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function count(
        Request $request
    ): JsonResponse {

        return response()->json([
            'count' => $this->saleReturnService->count(
                $request->all()
            ),
        ]);
    }

    public function totalAmount(
        Request $request
    ): JsonResponse {

        return response()->json([
            'total_amount' => $this->saleReturnService->totalAmount(
                $request->all()
            ),
        ]);
    }
}
