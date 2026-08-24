<?php

namespace App\Http\Controllers\Api\V1\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Purchase\ChangePurchaseReturnStatusRequest;
use App\Http\Requests\Admin\Purchase\StorePurchaseReturnRequest;
use App\Http\Requests\Admin\Purchase\UpdatePurchaseReturnRequest;
use App\Http\Resources\Purchase\PurchaseReturnCollection;
use App\Http\Resources\Purchase\PurchaseReturnResource;
use App\Services\Contracts\PurchaseReturnServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function __construct(
        protected PurchaseReturnServiceInterface $purchaseReturnService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): PurchaseReturnCollection {

        return new PurchaseReturnCollection(
            $this->purchaseReturnService->paginate(
                perPage: (int) $request->input('per_page', 15),
                filters: $request->all()
            )
        );
    }

    public function trash(
        Request $request
    ): PurchaseReturnCollection {

        return new PurchaseReturnCollection(
            $this->purchaseReturnService->trashedPaginate(
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
    ): PurchaseReturnResource {

        return new PurchaseReturnResource(
            $this->purchaseReturnService->findOrFail($id)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StorePurchaseReturnRequest $request
    ): PurchaseReturnResource {

        return new PurchaseReturnResource(
            $this->purchaseReturnService->create(
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
        UpdatePurchaseReturnRequest $request,
        int $id
    ): PurchaseReturnResource {

        return new PurchaseReturnResource(
            $this->purchaseReturnService->update(
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

        $this->purchaseReturnService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase return deleted successfully.',
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

        $this->purchaseReturnService->restore($id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase return restored successfully.',
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

        $this->purchaseReturnService->forceDelete($id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase return permanently deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        ChangePurchaseReturnStatusRequest $request,
        int $id
    ): PurchaseReturnResource {

        return new PurchaseReturnResource(
            $this->purchaseReturnService->changeStatus(
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
            'count' => $this->purchaseReturnService->count(
                $request->all()
            ),
        ]);
    }

    public function totalAmount(
        Request $request
    ): JsonResponse {

        return response()->json([
            'total_amount' => $this->purchaseReturnService->totalAmount(
                $request->all()
            ),
        ]);
    }
}
