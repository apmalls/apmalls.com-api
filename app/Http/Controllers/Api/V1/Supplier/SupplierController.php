<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\ChangeSupplierStatusRequest;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Supplier Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $query = Supplier::query()
                ->latest();

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('supplier_code', 'ILIKE', "%{$search}%")
                        ->orWhere('company_name', 'ILIKE', "%{$search}%")
                        ->orWhere('contact_person', 'ILIKE', "%{$search}%")
                        ->orWhere('mobile', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%")
                        ->orWhere('gst_number', 'ILIKE', "%{$search}%");

                });

            }

            /*
            |--------------------------------------------------------------------------
            | Status Filter
            |--------------------------------------------------------------------------
            */

            if ($request->filled('status')) {

                $query->where(
                    'is_active',
                    $request->boolean('status')
                );

            }

            $suppliers = $query->paginate(
                $request->integer('per_page', 10)
            );

            return response()->json([

                'success' => true,

                'message' => 'Suppliers fetched successfully.',

                'data' => $suppliers,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Store Supplier
     */
    public function store(
        StoreSupplierRequest $request
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $supplier = Supplier::create([

                'user_id' => $request->user_id,

                'supplier_code' => $this->generateSupplierCode(),

                'company_name' => $request->company_name,

                'contact_person' => $request->contact_person,

                'mobile' => $request->mobile,

                'alternate_mobile' => $request->alternate_mobile,

                'email' => $request->email,

                'gst_number' => $request->gst_number,

                'pan_number' => $request->pan_number,

                'bank_name' => $request->bank_name,

                'account_holder_name' => $request->account_holder_name,

                'account_number' => $request->account_number,

                'ifsc_code' => $request->ifsc_code,

                'opening_balance' => $request->opening_balance ?? 0,

                'credit_limit' => $request->credit_limit ?? 0,

                'notes' => $request->notes,

                'is_active' => $request->boolean('is_active'),

                'created_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Supplier created successfully.',

                'data' => $supplier,

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Generate Supplier Code
     */
    private function generateSupplierCode(): string
    {
        do {

            $nextId = (Supplier::max('id') ?? 0) + 1;

            $code = 'SUP-' . str_pad(
                $nextId,
                6,
                '0',
                STR_PAD_LEFT
            );

        } while (
            Supplier::where(
                'supplier_code',
                $code
            )->exists()
        );

        return $code;
    }


    /**
     * Display Supplier
     */
    public function show($id): JsonResponse
    {
        try {

            $supplier = Supplier::findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Supplier fetched successfully.',

                'data' => $supplier,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Update Supplier
     */
    public function update(
        UpdateSupplierRequest $request,
        $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $supplier = Supplier::findOrFail($id);

            $supplier->update([

                'user_id' => $request->user_id,

                /*
                |--------------------------------------------------------------------------
                | Supplier Code Never Changes
                |--------------------------------------------------------------------------
                */

                'supplier_code' => $supplier->supplier_code,

                'company_name' => $request->company_name,

                'contact_person' => $request->contact_person,

                'mobile' => $request->mobile,

                'alternate_mobile' => $request->alternate_mobile,

                'email' => $request->email,

                'gst_number' => $request->gst_number,

                'pan_number' => $request->pan_number,

                'bank_name' => $request->bank_name,

                'account_holder_name' => $request->account_holder_name,

                'account_number' => $request->account_number,

                'ifsc_code' => $request->ifsc_code,

                'opening_balance' => $request->opening_balance,

                'credit_limit' => $request->credit_limit,

                'notes' => $request->notes,

                'is_active' => $request->boolean('is_active'),

                'updated_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Supplier updated successfully.',

                'data' => $supplier->fresh(),

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }


    /**
     * Change Supplier Status
     */
    public function changeStatus(
        ChangeSupplierStatusRequest $request,
        $id
    ): JsonResponse {

        try {

            $supplier = Supplier::findOrFail($id);

            $supplier->update([

                'is_active' => $request->boolean('is_active'),

                'updated_by' => auth()->id(),

            ]);

            return response()->json([

                'success' => true,

                'message' => 'Supplier status updated successfully.',

                'data' => $supplier,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }

    }

    /**
     * Soft Delete Supplier
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $supplier = Supplier::findOrFail($id);

            $supplier->delete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Supplier deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Trash Supplier List
     */
    public function trash(Request $request): JsonResponse
    {
        try {

            $query = Supplier::onlyTrashed()
                ->latest('deleted_at');

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('supplier_code', 'ILIKE', "%{$search}%")
                        ->orWhere('company_name', 'ILIKE', "%{$search}%")
                        ->orWhere('contact_person', 'ILIKE', "%{$search}%")
                        ->orWhere('mobile', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%");

                });

            }

            return response()->json([

                'success' => true,

                'message' => 'Deleted suppliers fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }

    }

    /**
     * Restore Supplier
     */
    public function restore($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $supplier = Supplier::onlyTrashed()
                ->findOrFail($id);

            $supplier->restore();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Supplier restored successfully.',

                'data' => $supplier,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Permanently Delete Supplier
     */
    public function forceDelete($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $supplier = Supplier::onlyTrashed()
                ->findOrFail($id);

            $supplier->forceDelete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Supplier permanently deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }


}


