<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Customer\ChangeCustomerStatusRequest;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer\Customer;
use Illuminate\Http\JsonResponse;


class CustomerController extends Controller
{
    /**
     * Customer Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $query = Customer::query()->latest();

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('customer_code', 'ILIKE', "%{$search}%")
                        ->orWhere('first_name', 'ILIKE', "%{$search}%")
                        ->orWhere('last_name', 'ILIKE', "%{$search}%")
                        ->orWhere('mobile', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%")
                        ->orWhere('company_name', 'ILIKE', "%{$search}%")
                        ->orWhere('gst_number', 'ILIKE', "%{$search}%");

                });

            }

            /*
            |--------------------------------------------------------------------------
            | Customer Type
            |--------------------------------------------------------------------------
            */

            if ($request->filled('customer_type')) {

                $query->where(
                    'customer_type',
                    $request->customer_type
                );

            }

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            if ($request->filled('status')) {

                $query->where(
                    'is_active',
                    $request->boolean('status')
                );

            }

            $customers = $query->paginate(
                $request->integer('per_page', 10)
            );

            return response()->json([

                'success' => true,

                'message' => 'Customers fetched successfully.',

                'data' => $customers,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Store Customer
     */
    public function store(
        StoreCustomerRequest $request
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $customer = Customer::create([

                'user_id' => $request->user_id,

                'customer_code' => $this->generateCustomerCode(),

                'customer_type' => $request->customer_type,

                'first_name' => $request->first_name,

                'last_name' => $request->last_name,

                'mobile' => $request->mobile,

                'alternate_mobile' => $request->alternate_mobile,

                'email' => $request->email,

                'company_name' => $request->company_name,

                'gst_number' => $request->gst_number,

                'date_of_birth' => $request->date_of_birth,

                'anniversary_date' => $request->anniversary_date,

                'opening_balance' => $request->opening_balance ?? 0,

                'credit_limit' => $request->credit_limit ?? 0,

                'reward_points' => $request->reward_points ?? 0,

                'notes' => $request->notes,

                'is_active' => $request->boolean('is_active'),

                'created_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Customer created successfully.',

                'data' => $customer,

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Generate Customer Code
     */
    private function generateCustomerCode(): string
    {
        do {

            $nextId = (Customer::max('id') ?? 0) + 1;

            $code = 'CUS-' . str_pad(
                $nextId,
                6,
                '0',
                STR_PAD_LEFT
            );

        } while (
            Customer::where(
                'customer_code',
                $code
            )->exists()
        );

        return $code;
    }

    /**
     * Display Customer
     */
    public function show($id): JsonResponse
    {
        try {

            $customer = Customer::findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Customer fetched successfully.',

                'data' => $customer,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }


    /**
     * Update Customer
     */
    public function update(
        UpdateCustomerRequest $request,
        $id
    ): JsonResponse {

        $this->beginTransaction();

        try {

            $customer = Customer::findOrFail($id);

            $customer->update([

                'user_id' => $request->user_id,

                /*
                |--------------------------------------------------------------------------
                | Customer Code Never Changes
                |--------------------------------------------------------------------------
                */

                'customer_code' => $customer->customer_code,

                'customer_type' => $request->customer_type,

                'first_name' => $request->first_name,

                'last_name' => $request->last_name,

                'mobile' => $request->mobile,

                'alternate_mobile' => $request->alternate_mobile,

                'email' => $request->email,

                'company_name' => $request->company_name,

                'gst_number' => $request->gst_number,

                'date_of_birth' => $request->date_of_birth,

                'anniversary_date' => $request->anniversary_date,

                'opening_balance' => $request->opening_balance,

                'credit_limit' => $request->credit_limit,

                'reward_points' => $request->reward_points,

                'notes' => $request->notes,

                'is_active' => $request->boolean('is_active'),

                'updated_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Customer updated successfully.',

                'data' => $customer->fresh(),

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }


    /**
     * Change Customer Status
     */
    public function changeStatus(
        ChangeCustomerStatusRequest $request,
        $id
    ): JsonResponse {

        try {

            $customer = Customer::findOrFail($id);

            $customer->update([

                'is_active' => $request->boolean('is_active'),

                'updated_by' => auth()->id(),

            ]);

            return response()->json([

                'success' => true,

                'message' => 'Customer status updated successfully.',

                'data' => $customer,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }

    }

    /**
     * Soft Delete Customer
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $customer = Customer::findOrFail($id);

            $customer->delete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Customer deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Trash Customer List
     */
    public function trash(Request $request): JsonResponse
    {
        try {

            $query = Customer::onlyTrashed()
                ->latest('deleted_at');

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('customer_code', 'ILIKE', "%{$search}%")
                        ->orWhere('first_name', 'ILIKE', "%{$search}%")
                        ->orWhere('last_name', 'ILIKE', "%{$search}%")
                        ->orWhere('mobile', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%")
                        ->orWhere('company_name', 'ILIKE', "%{$search}%");

                });

            }

            return response()->json([

                'success' => true,

                'message' => 'Deleted customers fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Restore Customer
     */
    public function restore($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $customer = Customer::onlyTrashed()
                ->findOrFail($id);

            $customer->restore();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Customer restored successfully.',

                'data' => $customer,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Permanently Delete Customer
     */
    public function forceDelete($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $customer = Customer::onlyTrashed()
                ->findOrFail($id);

            $customer->forceDelete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Customer permanently deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }




}
