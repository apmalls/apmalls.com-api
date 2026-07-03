<?php

namespace App\Http\Controllers\Api\V1\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StorePurchaseOrderRequest;
use App\Models\Purchase\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\NumberHelper;
use App\Helpers\StockHelper;
use App\Http\Requests\Purchase\UpdatePurchaseOrderRequest;
use App\Http\Requests\Purchase\ChangePurchaseStatusRequest;

class PurchaseOrderController extends Controller
{
    /**
     * Purchase Order Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $query = PurchaseOrder::with([
                'supplier',
                'items.product',
            ])->latest();

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('purchase_no', 'ILIKE', "%{$search}%")
                        ->orWhere('invoice_no', 'ILIKE', "%{$search}%")
                        ->orWhereHas('supplier', function ($supplier) use ($search) {

                            $supplier->where('company_name', 'ILIKE', "%{$search}%");

                        });

                });

            }

            if ($request->filled('status')) {

                $query->where('status', $request->status);

            }

            return response()->json([

                'success' => true,

                'message' => 'Purchase orders fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Store Purchase Order
     */
    public function store(
        StorePurchaseOrderRequest $request
    ): JsonResponse {

        $this->beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Purchase Number
            |--------------------------------------------------------------------------
            */

            $purchaseNo = NumberHelper::generate(
                PurchaseOrder::class,
                'purchase_no',
                'PO'
            );

            /*
            |--------------------------------------------------------------------------
            | Purchase Order
            |--------------------------------------------------------------------------
            */

            $purchase = PurchaseOrder::create([

                'supplier_id' => $request->supplier_id,

                'purchase_no' => $purchaseNo,

                'invoice_no' => $request->invoice_no,

                'purchase_date' => $request->purchase_date,

                'invoice_date' => $request->invoice_date,

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


            /*
            |--------------------------------------------------------------------------
            | Purchase Items
            |--------------------------------------------------------------------------
            */

            foreach ($request->items as $item) {

                $purchase->items()->create([

                    'product_id' => $item['product_id'],

                    'purchase_price' => $item['purchase_price'],

                    'selling_price' => $item['selling_price'] ?? 0,

                    'quantity' => $item['quantity'],

                    'received_quantity' => $item['quantity'],

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

                'message' => 'Purchase order created successfully.',

                'data' => $purchase->load([
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
     * Display Purchase Order
     */
    public function show($id): JsonResponse
    {
        try {

            $purchase = PurchaseOrder::with([

                'supplier',

                'items.product',

            ])->findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Purchase order fetched successfully.',

                'data' => $purchase,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Update Purchase Order
     */
    public function update(
        UpdatePurchaseOrderRequest $request,
        $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $purchase = PurchaseOrder::findOrFail($id);

            $purchase->update([

                'supplier_id' => $request->supplier_id,

                'invoice_no' => $request->invoice_no,

                'purchase_date' => $request->purchase_date,

                'invoice_date' => $request->invoice_date,

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

            $purchase->items()->delete();

            /*
            |--------------------------------------------------------------------------
            | Insert New Items
            |--------------------------------------------------------------------------
            */

            foreach ($request->items as $item) {

                $purchase->items()->create([

                    'product_id' => $item['product_id'],

                    'purchase_price' => $item['purchase_price'],

                    'selling_price' => $item['selling_price'] ?? 0,

                    'quantity' => $item['quantity'],

                    'received_quantity' => $item['quantity'],

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

                'message' => 'Purchase order updated successfully.',

                'data' => $purchase->load([

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
     * Change Purchase Status
     */
    public function changeStatus(
        ChangePurchaseStatusRequest $request,
        $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $purchase = PurchaseOrder::with('items')->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Receive
            |--------------------------------------------------------------------------
            */

            if (
                $purchase->status === 'Received'
                && $request->status === 'Received'
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Purchase already received.',
                ], 422);

            }

            /*
            |--------------------------------------------------------------------------
            | Stock Update
            |--------------------------------------------------------------------------
            */

            if ($request->status === 'Received') {

                foreach ($purchase->items as $item) {

                    StockHelper::increase(

                        productId: $item->product_id,

                        quantity: $item->received_quantity,

                        referenceType: 'Purchase',

                        referenceId: $purchase->id,

                        remarks: 'Purchase Received'

                    );

                }

            }

            /*
            |--------------------------------------------------------------------------
            | Update Status
            |--------------------------------------------------------------------------
            */

            $purchase->update([

                'status' => $request->status,

                'updated_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Purchase status updated successfully.',

                'data' => $purchase,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Soft Delete Purchase
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $purchase = PurchaseOrder::findOrFail($id);

            $purchase->delete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Purchase order deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Trash Purchase Orders
     */
    public function trash(Request $request): JsonResponse
    {
        try {

            $query = PurchaseOrder::onlyTrashed()
                ->with('supplier')
                ->latest('deleted_at');

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('purchase_no', 'ILIKE', "%{$search}%")
                        ->orWhere('invoice_no', 'ILIKE', "%{$search}%");

                });

            }

            return response()->json([

                'success' => true,

                'message' => 'Deleted purchase orders fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Restore Purchase Order
     */
    public function restore($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $purchase = PurchaseOrder::onlyTrashed()
                ->findOrFail($id);

            $purchase->restore();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Purchase order restored successfully.',

                'data' => $purchase,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }


    /**
     * Permanently Delete Purchase Order
     */
    public function forceDelete($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $purchase = PurchaseOrder::onlyTrashed()
                ->findOrFail($id);

            $purchase->forceDelete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Purchase order permanently deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }



}
