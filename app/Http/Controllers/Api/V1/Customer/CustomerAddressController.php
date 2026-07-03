<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;

use App\Http\Requests\Customer\ChangeCustomerDefaultAddressRequest;
use App\Http\Requests\Customer\StoreCustomerAddressRequest;
use App\Http\Requests\Customer\UpdateCustomerAddressRequest;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    /**
     * Customer Address Listing
     */
    public function index($customerId): JsonResponse
    {
        try {

            $customer = Customer::findOrFail($customerId);

            $addresses = $customer->addresses()
                ->latest()
                ->get();

            return response()->json([

                'success' => true,

                'message' => 'Customer addresses fetched successfully.',

                'data' => $addresses,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Store Customer Address
     */
    public function store(
        StoreCustomerAddressRequest $request,
        $customerId
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $customer = Customer::findOrFail($customerId);

            /*
            |--------------------------------------------------------------------------
            | Make Previous Default False
            |--------------------------------------------------------------------------
            */

            if ($request->boolean('is_default')) {

                CustomerAddress::where(
                    'customer_id',
                    $customer->id
                )->update([

                            'is_default' => false,

                        ]);

            }

            $address = CustomerAddress::create([

                'customer_id' => $customer->id,

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

                'message' => 'Customer address created successfully.',

                'data' => $address,

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Display Customer Address
     */
    public function show($id): JsonResponse
    {
        try {

            $address = CustomerAddress::findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Customer address fetched successfully.',

                'data' => $address,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Update Customer Address
     */
    public function update(
        UpdateCustomerAddressRequest $request,
        $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $address = CustomerAddress::findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Make Previous Default False
            |--------------------------------------------------------------------------
            */

            if ($request->boolean('is_default')) {

                CustomerAddress::where(
                    'customer_id',
                    $address->customer_id
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

                'message' => 'Customer address updated successfully.',

                'data' => $address->fresh(),

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Soft Delete Customer Address
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $address = CustomerAddress::findOrFail($id);

            $address->delete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Customer address deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Change Default Customer Address
     */
    public function changeDefault(
        ChangeCustomerDefaultAddressRequest $request, // Fixed request class name
        $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $address = CustomerAddress::findOrFail($id); // Changed to CustomerAddress

            CustomerAddress::where( // Changed to CustomerAddress
                'customer_id', // Changed to customer_id
                $address->customer_id
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

                'message' => 'Default customer address updated successfully.', // Changed message

                'data' => $address->fresh(),

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }

    }

    /**
     * Customer Address Trash List
     */
    public function trash($customerId): JsonResponse // Changed parameter name
    {
        try {

            Customer::findOrFail($customerId); // Changed to Customer

            $addresses = CustomerAddress::onlyTrashed() // Changed to CustomerAddress
                ->where('customer_id', $customerId) // Changed to customer_id
                ->latest('deleted_at')
                ->get();

            return response()->json([

                'success' => true,

                'message' => 'Deleted customer addresses fetched successfully.', // Changed message

                'data' => $addresses,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Restore Customer Address
     */
    public function restore($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $address = CustomerAddress::onlyTrashed() // Changed to CustomerAddress
                ->findOrFail($id);

            $address->restore();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Customer address restored successfully.', // Changed message

                'data' => $address,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Permanently Delete Customer Address
     */
    public function forceDelete($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $address = CustomerAddress::onlyTrashed() // Changed to CustomerAddress
                ->findOrFail($id);

            $address->forceDelete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Customer address permanently deleted successfully.', // Changed message

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }


}
