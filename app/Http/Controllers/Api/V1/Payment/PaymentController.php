<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ChangePaymentStatusRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * Display Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $payments = $this->paymentService->paginate(
                $request->all()
            );

            return response()->json([

                'success' => true,

                'message' => 'Payments fetched successfully.',

                'data' => $payments,

            ]);

        } catch (Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }
    }

    /**
     * Store Payment
     */
    public function store(
        StorePaymentRequest $request
    ): JsonResponse {

        try {

            $payment = $this->paymentService->store(
                $request->validated()
            );

            return response()->json([

                'success' => true,

                'message' => 'Payment created successfully.',

                'data' => $payment,

            ], 201);

        } catch (Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }

    /**
     * Show Payment
     */
    public function show(
        int $id
    ): JsonResponse {

        try {

            $payment = $this->paymentService->find($id);

            return response()->json([

                'success' => true,

                'message' => 'Payment fetched successfully.',

                'data' => $payment,

            ]);

        } catch (Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }

    /**
     * Update Payment
     */
    public function update(
        UpdatePaymentRequest $request,
        int $id
    ): JsonResponse {

        try {

            $payment = $this->paymentService->update(
                $id,
                $request->validated()
            );

            return response()->json([

                'success' => true,

                'message' => 'Payment updated successfully.',

                'data' => $payment,

            ]);

        } catch (Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }

    /**
     * Change Payment Status
     */
    public function changeStatus(
        ChangePaymentStatusRequest $request,
        int $id
    ): JsonResponse {

        try {

            $payment = $this->paymentService->changeStatus(
                $id,
                $request->validated()['status']
            );

            return response()->json([

                'success' => true,

                'message' => 'Payment status updated successfully.',

                'data' => $payment,

            ]);

        } catch (Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }

    public function trash(Request $request): JsonResponse
    {
        try {

            $payments = $this->paymentService->trash(
                $request->all()
            );

            return response()->json([

                'success' => true,

                'message' => 'Trashed payments fetched successfully.',

                'data' => $payments,

            ]);

        } catch (Throwable $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Delete Payment
     */
    public function destroy(
        int $id
    ): JsonResponse {

        try {

            $this->paymentService->delete($id);

            return response()->json([

                'success' => true,

                'message' => 'Payment deleted successfully.',

            ]);

        } catch (Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }

    /**
     * Restore Payment
     */
    public function restore(
        int $id
    ): JsonResponse {

        try {

            $this->paymentService->restore($id);

            return response()->json([

                'success' => true,

                'message' => 'Payment restored successfully.',

            ]);

        } catch (Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }

    /**
     * Force Delete
     */
    public function forceDelete(
        int $id
    ): JsonResponse {

        try {

            $this->paymentService->forceDelete($id);

            return response()->json([

                'success' => true,

                'message' => 'Payment permanently deleted.',

            ]);

        } catch (Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }

    }
}
