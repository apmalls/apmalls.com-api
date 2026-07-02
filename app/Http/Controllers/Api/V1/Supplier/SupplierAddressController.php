<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\ChangeSupplierDefaultAddressRequest;
use App\Http\Requests\Supplier\StoreSupplierAddressRequest;
use App\Http\Requests\Supplier\UpdateSupplierAddressRequest;
use App\Http\Requests\Supplier\ChangeDefaultSupplierAddressRequest;
use App\Models\Supplier;
use App\Models\Supplier\SupplierAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierAddressController extends Controller
{
    /**
     * Supplier Address Listing
     */
    public function index($supplierId): JsonResponse
    {
        try {

            $supplier = Supplier::findOrFail($supplierId);

            $addresses = $supplier->addresses()
                ->latest()
                ->get();

            return response()->json([

                'success' => true,

                'message' => 'Supplier addresses fetched successfully.',

                'data' => $addresses,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Store Supplier Address
     */
    public function store(
        StoreSupplierAddressRequest $request,
        $supplierId
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $supplier = Supplier::findOrFail($supplierId);

            /*
            |--------------------------------------------------------------------------
            | Make Previous Default False
            |--------------------------------------------------------------------------
            */

            if ($request->boolean('is_default')) {

                SupplierAddress::where(
                    'supplier_id',
                    $supplier->id
                )->update([

                            'is_default' => false,

                        ]);

            }

            $address = SupplierAddress::create([

                'supplier_id' => $supplier->id,

                'address_type' => $request->address_type,

                'contact_person' => $request->contact_person,

                'mobile' => $request->mobile,

                'alternate_mobile' => $request->alternate_mobile,

                'email' => $request->email,

                'address_line_1' => $request->address_line_1,

                'address_line_2' => $request->address_line_2,

                'landmark' => $request->landmark,

                'city' => $request->city,

                'state' => $request->state,

                'country' => $request->country,

                'postal_code' => $request->postal_code,

                'is_default' => $request->boolean('is_default'),

                'created_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Supplier address created successfully.',

                'data' => $address,

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Display Supplier Address
     */
    public function show($id): JsonResponse
    {
        try {

            $address = SupplierAddress::findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Supplier address fetched successfully.',

                'data' => $address,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Update Supplier Address
     */
    public function update(
        UpdateSupplierAddressRequest $request,
        $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $address = SupplierAddress::findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Make Previous Default False
            |--------------------------------------------------------------------------
            */

            if ($request->boolean('is_default')) {

                SupplierAddress::where(
                    'supplier_id',
                    $address->supplier_id
                )->where('id', '!=', $address->id)
                    ->update([

                        'is_default' => false,

                    ]);

            }

            $address->update([

                'address_type' => $request->address_type,

                'contact_person' => $request->contact_person,

                'mobile' => $request->mobile,

                'alternate_mobile' => $request->alternate_mobile,

                'email' => $request->email,

                'address_line_1' => $request->address_line_1,

                'address_line_2' => $request->address_line_2,

                'landmark' => $request->landmark,

                'city' => $request->city,

                'state' => $request->state,

                'country' => $request->country,

                'postal_code' => $request->postal_code,

                'is_default' => $request->boolean('is_default'),

                'updated_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Supplier address updated successfully.',

                'data' => $address->fresh(),

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Soft Delete Supplier Address
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $address = SupplierAddress::findOrFail($id);

            $address->delete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Supplier address deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Change Default Supplier Address
     */
    public function changeDefault(
        ChangeSupplierDefaultAddressRequest $request,
        $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $address = SupplierAddress::findOrFail($id);

            SupplierAddress::where(
                'supplier_id',
                $address->supplier_id
            )->update([

                        'is_default' => false,

                        'updated_by' => auth()->id(),

                    ]);

            $address->update([

                'is_default' => true,

                'updated_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Default supplier address updated successfully.',

                'data' => $address->fresh(),

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Supplier Address Trash List
     */
    public function trash($supplierId): JsonResponse
    {
        try {

            Supplier::findOrFail($supplierId);

            $addresses = SupplierAddress::onlyTrashed()
                ->where('supplier_id', $supplierId)
                ->latest('deleted_at')
                ->get();

            return response()->json([

                'success' => true,

                'message' => 'Deleted supplier addresses fetched successfully.',

                'data' => $addresses,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Restore Supplier Address
     */
    public function restore($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $address = SupplierAddress::onlyTrashed()
                ->findOrFail($id);

            $address->restore();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Supplier address restored successfully.',

                'data' => $address,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Permanently Delete Supplier Address
     */
    public function forceDelete($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $address = SupplierAddress::onlyTrashed()
                ->findOrFail($id);

            $address->forceDelete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Supplier address permanently deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }



}
