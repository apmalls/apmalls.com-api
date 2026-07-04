<?php

namespace App\Http\Controllers\Api\V1\Purchase;

use App\Helpers\NumberHelper;
use App\Helpers\StockHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\ChangePurchaseReturnStatusRequest;
use App\Http\Requests\Purchase\StorePurchaseReturnRequest;
use App\Http\Requests\Purchase\UpdatePurchaseReturnRequest;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\PurchaseReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    /**
     * Purchase Return Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $query = PurchaseReturn::with([
                'purchaseOrder',
                'supplier',
                'items.product',
            ])->latest();

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('return_no', 'ILIKE', "%{$search}%");

                });

            }

            return response()->json([

                'success' => true,

                'message' => 'Purchase return list fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Store Purchase Return
     */
    public function store(StorePurchaseReturnRequest $request): JsonResponse
    {
        $this->beginTransaction();

        try {

            $purchaseReturn = PurchaseReturn::create([

                'purchase_order_id' => $request->purchase_order_id,

                'supplier_id' => $request->supplier_id,

                'return_no' => NumberHelper::generate(
                    PurchaseReturn::class,
                    'return_no',
                    'PR'
                ),

                'return_date' => $request->return_date,

                'total_amount' => $request->total_amount,

                'remarks' => $request->remarks,

                'status' => 'Draft',

                'created_by' => auth()->id(),

            ]);

            foreach ($request->items as $item) {

                $purchaseReturn->items()->create([

                    'purchase_order_item_id' => $item['purchase_order_item_id'],

                    'product_id' => $item['product_id'],

                    'purchase_price' => $item['purchase_price'],

                    'quantity' => $item['quantity'],

                    'line_total' => $item['line_total'],

                ]);

            }

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Purchase return created successfully.',

                'data' => $purchaseReturn->load([
                    'purchaseOrder',
                    'supplier',
                    'items.product',
                ]),

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Show Purchase Return
     */
    public function show(int $id): JsonResponse
    {
        try {

            $purchaseReturn = PurchaseReturn::with([
                'purchaseOrder',
                'supplier',
                'items.product',
            ])->findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Purchase return fetched successfully.',

                'data' => $purchaseReturn,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Update Purchase Return
     */
    public function update(
        UpdatePurchaseReturnRequest $request,
        int $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $purchaseReturn = PurchaseReturn::with('items')->findOrFail($id);

            if ($purchaseReturn->status === 'Completed') {

                return response()->json([
                    'success' => false,
                    'message' => 'Completed purchase return cannot be updated.',
                ], 422);

            }

            $purchaseReturn->update([

                'purchase_order_id' => $request->purchase_order_id,

                'supplier_id' => $request->supplier_id,

                'return_date' => $request->return_date,

                'total_amount' => $request->total_amount,

                'remarks' => $request->remarks,

                'updated_by' => auth()->id(),

            ]);

            $purchaseReturn->items()->delete();

            foreach ($request->items as $item) {

                $purchaseReturn->items()->create([

                    'purchase_order_item_id' => $item['purchase_order_item_id'],

                    'product_id' => $item['product_id'],

                    'purchase_price' => $item['purchase_price'],

                    'quantity' => $item['quantity'],

                    'line_total' => $item['line_total'],

                ]);

            }

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Purchase return updated successfully.',

                'data' => $purchaseReturn->load([
                    'purchaseOrder',
                    'supplier',
                    'items.product',
                ]),

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Change Purchase Return Status
     */
    public function changeStatus(
        ChangePurchaseReturnStatusRequest $request,
        int $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $purchaseReturn = PurchaseReturn::with('items')
                ->findOrFail($id);

            if (
                $purchaseReturn->status === 'Completed' &&
                $request->status === 'Completed'
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Purchase return already completed.',
                ], 422);

            }

            if ($request->status === 'Completed') {

                foreach ($purchaseReturn->items as $item) {

                    StockHelper::decrease(

                        productId: $item->product_id,

                        quantity: $item->quantity,

                        referenceType: 'Purchase Return',

                        referenceId: $purchaseReturn->id,

                        remarks: 'Purchase Return Completed'

                    );

                }

            }

            $purchaseReturn->update([

                'status' => $request->status,

                'updated_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Purchase return status updated successfully.',

                'data' => $purchaseReturn,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Soft Delete Purchase Return
     */
    public function destroy(int $id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $purchaseReturn = PurchaseReturn::findOrFail($id);

            if ($purchaseReturn->status === 'Completed') {

                return response()->json([

                    'success' => false,

                    'message' => 'Completed purchase return cannot be deleted.',

                ], 422);

            }

            $purchaseReturn->delete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Purchase return deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Trash Purchase Returns
     */
    public function trash(Request $request): JsonResponse
    {
        try {

            $query = PurchaseReturn::onlyTrashed()
                ->with([
                    'supplier',
                    'purchaseOrder',
                ])
                ->latest('deleted_at');

            return response()->json([

                'success' => true,

                'message' => 'Deleted purchase returns fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Restore Purchase Return
     */
    public function restore(int $id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $purchaseReturn = PurchaseReturn::onlyTrashed()
                ->findOrFail($id);

            $purchaseReturn->restore();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Purchase return restored successfully.',

                'data' => $purchaseReturn,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Permanently Delete Purchase Return
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $purchaseReturn = PurchaseReturn::onlyTrashed()
                ->findOrFail($id);

            $purchaseReturn->forceDelete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Purchase return permanently deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

}
