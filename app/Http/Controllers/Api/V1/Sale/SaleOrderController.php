<?php

namespace App\Http\Controllers\Api\V1\Sale;

use App\Helpers\NumberHelper;
use App\Helpers\StockHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\StoreSaleOrderRequest;
use App\Http\Requests\Sale\UpdateSaleOrderRequest;
use App\Models\Sale\SaleOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleOrderController extends Controller
{
    /**
     * Sale Order Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $query = SaleOrder::with([
                'customer',
                'items.product',
            ])->latest();

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('sale_no', 'ILIKE', "%{$search}%")
                        ->orWhere('invoice_no', 'ILIKE', "%{$search}%")
                        ->orWhereHas('customer', function ($customer) use ($search) {

                            $customer->where('name', 'ILIKE', "%{$search}%");

                        });

                });

            }

            if ($request->filled('status')) {

                $query->where('status', $request->status);

            }

            return response()->json([

                'success' => true,

                'message' => 'Sale orders fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Store Sale Order
     */
    public function store(
        StoreSaleOrderRequest $request
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $sale = SaleOrder::create([

                'customer_id' => $request->customer_id,

                'sale_no' => NumberHelper::generate(
                    SaleOrder::class,
                    'sale_no',
                    'SO'
                ),

                'invoice_no' => $request->invoice_no,

                'sale_date' => $request->sale_date,

                'sub_total' => $request->sub_total,

                'discount_amount' => $request->discount_amount ?? 0,

                'tax_amount' => $request->tax_amount ?? 0,

                'shipping_charge' => $request->shipping_charge ?? 0,

                'other_charge' => $request->other_charge ?? 0,

                'grand_total' => $request->grand_total,

                'paid_amount' => $request->paid_amount ?? 0,

                'due_amount' => $request->grand_total - ($request->paid_amount ?? 0),

                'remarks' => $request->remarks,

                'status' => 'Draft',

                'created_by' => auth()->id(),

            ]);

            foreach ($request->items as $item) {

                $sale->items()->create([

                    'product_id' => $item['product_id'],

                    'purchase_price' => $item['purchase_price'],

                    'selling_price' => $item['selling_price'],

                    'quantity' => $item['quantity'],

                    'tax_percent' => $item['tax_percent'] ?? 0,

                    'tax_amount' => $item['tax_amount'] ?? 0,

                    'discount_percent' => $item['discount_percent'] ?? 0,

                    'discount_amount' => $item['discount_amount'] ?? 0,

                    'line_total' => $item['line_total'],

                ]);

            }

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale order created successfully.',

                'data' => $sale->load([
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
     * Display Sale Order
     */
    public function show(int $id): JsonResponse
    {
        try {

            $sale = SaleOrder::with([

                'customer',

                'items.product',

            ])->findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Sale order fetched successfully.',

                'data' => $sale,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }


    /**
     * Update Sale Order
     */
    public function update(
        UpdateSaleOrderRequest $request,
        int $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $sale = SaleOrder::with('items')->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Completed Sale Cannot Update
            |--------------------------------------------------------------------------
            */

            if ($sale->status === 'Completed') {

                return response()->json([

                    'success' => false,

                    'message' => 'Completed sale order cannot be updated.',

                ], 422);

            }

            /*
            |--------------------------------------------------------------------------
            | Update Sale Order
            |--------------------------------------------------------------------------
            */

            $sale->update([

                'customer_id' => $request->customer_id,

                'invoice_no' => $request->invoice_no,

                'sale_date' => $request->sale_date,

                'sub_total' => $request->sub_total,

                'discount_amount' => $request->discount_amount ?? 0,

                'tax_amount' => $request->tax_amount ?? 0,

                'shipping_charge' => $request->shipping_charge ?? 0,

                'other_charge' => $request->other_charge ?? 0,

                'grand_total' => $request->grand_total,

                'paid_amount' => $request->paid_amount ?? 0,

                'due_amount' => $request->grand_total - ($request->paid_amount ?? 0),

                'remarks' => $request->remarks,

                'updated_by' => auth()->id(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Delete Old Items
            |--------------------------------------------------------------------------
            */

            $sale->items()->delete();

            /*
            |--------------------------------------------------------------------------
            | Insert New Items
            |--------------------------------------------------------------------------
            */

            foreach ($request->items as $item) {

                $sale->items()->create([

                    'product_id' => $item['product_id'],

                    'purchase_price' => $item['purchase_price'],

                    'selling_price' => $item['selling_price'],

                    'quantity' => $item['quantity'],

                    'tax_percent' => $item['tax_percent'] ?? 0,

                    'tax_amount' => $item['tax_amount'] ?? 0,

                    'discount_percent' => $item['discount_percent'] ?? 0,

                    'discount_amount' => $item['discount_amount'] ?? 0,

                    'line_total' => $item['line_total'],

                ]);

            }

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale order updated successfully.',

                'data' => $sale->load([

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
     * Change Sale Status
     */
    public function changeStatus(
        ChangeSaleStatusRequest $request,
        int $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $sale = SaleOrder::with('items')->findOrFail($id);

            if (
                $sale->status === 'Completed' &&
                $request->status === 'Completed'
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale order already completed.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Stock Update
            |--------------------------------------------------------------------------
            */

            if ($request->status === 'Completed') {

                foreach ($sale->items as $item) {

                    StockHelper::decrease(

                        productId: $item->product_id,

                        quantity: $item->quantity,

                        referenceType: 'Sale',

                        referenceId: $sale->id,

                        remarks: 'Sale Completed'

                    );

                }

            }

            $sale->update([

                'status' => $request->status,

                'updated_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale status updated successfully.',

                'data' => $sale,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Soft Delete Sale
     */
    public function destroy(int $id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $sale = SaleOrder::with('items')->findOrFail($id);

            if ($sale->status === 'Completed') {

                foreach ($sale->items as $item) {

                    StockHelper::increase(

                        productId: $item->product_id,

                        quantity: $item->quantity,

                        referenceType: 'Sale Delete',

                        referenceId: $sale->id,

                        remarks: 'Sale Deleted'

                    );

                }

            }

            $sale->delete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale order deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Trash Sale Orders
     */
    public function trash(Request $request): JsonResponse
    {
        try {

            $query = SaleOrder::onlyTrashed()
                ->with('customer')
                ->latest('deleted_at');

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('sale_no', 'ILIKE', "%{$search}%")
                        ->orWhere('invoice_no', 'ILIKE', "%{$search}%");

                });

            }

            return response()->json([

                'success' => true,

                'message' => 'Deleted sale orders fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Restore Sale Order
     */
    public function restore(int $id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $sale = SaleOrder::onlyTrashed()
                ->with('items')
                ->findOrFail($id);

            $sale->restore();

            if ($sale->status === 'Completed') {

                foreach ($sale->items as $item) {

                    StockHelper::decrease(

                        productId: $item->product_id,

                        quantity: $item->quantity,

                        referenceType: 'Sale Restore',

                        referenceId: $sale->id,

                        remarks: 'Sale Restored'

                    );

                }

            }

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale order restored successfully.',

                'data' => $sale,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }


    /**
     * Permanently Delete Sale
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $sale = SaleOrder::onlyTrashed()->findOrFail($id);

            $sale->forceDelete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Sale order permanently deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

}
