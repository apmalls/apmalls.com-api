<?php

namespace App\Http\Controllers\Api\V1\Sale;

use App\Helpers\NumberHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\StoreSaleReturnRequest;
use App\Http\Requests\Sale\UpdateSaleReturnRequest;
use App\Models\Sale\SaleReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    /**
     * Sale Return Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $query = SaleReturn::with([
                'saleOrder',
                'customer',
                'items.product',
            ])->latest();

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('return_no', 'ILIKE', "%{$search}%");

                });

            }

            if ($request->filled('status')) {

                $query->where('status', $request->status);

            }

            return response()->json([

                'success' => true,

                'message' => 'Sale returns fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Store Sale Return
     */
    public function store(
        StoreSaleReturnRequest $request
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $saleReturn = SaleReturn::create([

                'sale_order_id' => $request->sale_order_id,

                'customer_id' => $request->customer_id,

                'return_no' => NumberHelper::generate(
                    SaleReturn::class,
                    'return_no',
                    'SR'
                ),

                'return_date' => $request->return_date,

                'total_amount' => $request->total_amount,

                'remarks' => $request->remarks,

                'status' => 'Draft',

                'created_by' => auth()->id(),

            ]);

            foreach ($request->items as $item) {

                $saleReturn->items()->create([

                    'sale_order_item_id' => $item['sale_order_item_id'],

                    'product_id' => $item['product_id'],

                    'selling_price' => $item['selling_price'],

                    'quantity' => $item['quantity'],

                    'line_total' => $item['line_total'],

                ]);

            }

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale return created successfully.',

                'data' => $saleReturn->load([

                    'saleOrder',

                    'customer',

                    'items.product',

                ]),

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Display Sale Return
     */
    public function show(int $id): JsonResponse
    {
        try {

            $saleReturn = SaleReturn::with([

                'saleOrder',

                'customer',

                'items.product',

            ])->findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Sale return fetched successfully.',

                'data' => $saleReturn,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Update Sale Return
     */
    public function update(
        UpdateSaleReturnRequest $request,
        int $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $saleReturn = SaleReturn::with('items')
                ->findOrFail($id);

            if ($saleReturn->status === 'Completed') {

                return response()->json([

                    'success' => false,

                    'message' => 'Completed sale return cannot be updated.',

                ], 422);

            }

            $saleReturn->update([

                'sale_order_id' => $request->sale_order_id,

                'customer_id' => $request->customer_id,

                'return_date' => $request->return_date,

                'total_amount' => $request->total_amount,

                'remarks' => $request->remarks,

                'updated_by' => auth()->id(),

            ]);

            $saleReturn->items()->delete();

            foreach ($request->items as $item) {

                $saleReturn->items()->create([

                    'sale_order_item_id' => $item['sale_order_item_id'],

                    'product_id' => $item['product_id'],

                    'selling_price' => $item['selling_price'],

                    'quantity' => $item['quantity'],

                    'line_total' => $item['line_total'],

                ]);

            }

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale return updated successfully.',

                'data' => $saleReturn->load([

                    'saleOrder',

                    'customer',

                    'items.product',

                ]),

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Change Sale Return Status
     */
    public function changeStatus(
        ChangeSaleReturnStatusRequest $request,
        int $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $saleReturn = SaleReturn::with('items')
                ->findOrFail($id);

            if (
                $saleReturn->status === 'Completed' &&
                $request->status === 'Completed'
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Sale return already completed.',
                ], 422);

            }

            /*
            |--------------------------------------------------------------------------
            | Stock Update
            |--------------------------------------------------------------------------
            */

            if ($request->status === 'Completed') {

                foreach ($saleReturn->items as $item) {

                    StockHelper::increase(

                        productId: $item->product_id,

                        quantity: $item->quantity,

                        referenceType: 'Sale Return',

                        referenceId: $saleReturn->id,

                        remarks: 'Sale Return Completed'

                    );

                }

            }

            $saleReturn->update([

                'status' => $request->status,

                'updated_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale return status updated successfully.',

                'data' => $saleReturn,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Soft Delete Sale Return
     */
    public function destroy(int $id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $saleReturn = SaleReturn::findOrFail($id);

            if ($saleReturn->status === 'Completed') {

                return response()->json([

                    'success' => false,

                    'message' => 'Completed sale return cannot be deleted.',

                ], 422);

            }

            $saleReturn->delete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale return deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Trash Sale Returns
     */
    public function trash(Request $request): JsonResponse
    {
        try {

            $query = SaleReturn::onlyTrashed()
                ->with([
                    'customer',
                    'saleOrder',
                ])
                ->latest('deleted_at');

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('return_no', 'ILIKE', "%{$search}%");

                });

            }

            return response()->json([

                'success' => true,

                'message' => 'Deleted sale returns fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Restore Sale Return
     */
    public function restore(int $id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $saleReturn = SaleReturn::onlyTrashed()
                ->findOrFail($id);

            $saleReturn->restore();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale return restored successfully.',

                'data' => $saleReturn,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Permanently Delete Sale Return
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $saleReturn = SaleReturn::onlyTrashed()
                ->findOrFail($id);

            $saleReturn->forceDelete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale return permanently deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }
}
