<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\CustomerAddressService;
use App\Http\Requests\Website\CustomerAddress\StoreCustomerAddressRequest;
use App\Http\Requests\Website\CustomerAddress\UpdateCustomerAddressRequest;

class CustomerAddressController extends Controller
{
    public function __construct(
        protected CustomerAddressService $addressService,
    ) {
    }

    /**
     * Customer Address Listing
     */
    public function index(): JsonResponse
    {
        try {

            $customerId = auth()->user()->customer->id;

            return response()->json([

                'success' => true,

                'message' => 'Customer addresses fetched successfully.',

                'data' => $this->addressService->index(
                    $customerId
                ),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Store Address
     */
    public function store(
        StoreCustomerAddressRequest $request
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            $address = $this->addressService->store(

                $customerId,

                $request->validated()

            );

            return response()->json([

                'success' => true,

                'message' => 'Address created successfully.',

                'data' => $address,

            ], 201);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Update Address
     */
    public function update(
        UpdateCustomerAddressRequest $request,
        int $id
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            $address = $this->addressService->update(

                $customerId,
                $id,


                $request->validated()

            );

            return response()->json([

                'success' => true,

                'message' => 'Address updated successfully.',

                'data' => $address,

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Delete Address
     */
    public function destroy(
        int $id
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            $this->addressService->delete(

                $customerId,

                $id

            );

            return response()->json([

                'success' => true,

                'message' => 'Address deleted successfully.',

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Default Address
     */
    public function default(): JsonResponse
    {
        try {

            $customerId = auth()->user()->customer->id;

            return response()->json([

                'success' => true,

                'message' => 'Default address fetched successfully.',

                'data' => $this->addressService->default(
                    $customerId
                ),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }
}
