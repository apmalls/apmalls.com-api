<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Payment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payment\ChangePaymentStatusRequest;
use App\Http\Requests\Admin\Payment\StorePaymentRequest;
use App\Http\Requests\Admin\Payment\UpdatePaymentRequest;
use App\Services\Contracts\PaymentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\Payment\PaymentCollection;
use App\Http\Resources\Payment\PaymentResource;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentServiceInterface $paymentService
    ) {
    }

    /**
     * Payment Listing
     */
    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->paginate(
            $request->all()
        );

        return response()->json([
            'success' => true,
            'message' => 'Payments fetched successfully.',
            'data' => new PaymentCollection($payments),
        ]);
    }

    /**
     * Payment Store
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully.',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    /**
     * Payment Details
     */
    public function show(int $id): JsonResponse
    {
        $payment = $this->paymentService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment details fetched successfully.',
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Payment Update
     */
    public function update(
        UpdatePaymentRequest $request,
        int $id
    ): JsonResponse {

        $payment = $this->paymentService->update(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully.',
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Soft Delete
     */
    public function destroy(int $id): JsonResponse
    {
        $this->paymentService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully.',
        ]);
    }

    /**
     * Trashed Payments
     */
    public function trashed(Request $request): JsonResponse
    {
        $payments = $this->paymentService->trashedPaginate(
            $request->all()
        );

        return response()->json([
            'success' => true,
            'message' => 'Deleted payments fetched successfully.',
            'data' => $payments,
        ]);
    }

    /**
     * Restore
     */
    public function restore(int $id): JsonResponse
    {
        $this->paymentService->restore($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment restored successfully.',
        ]);
    }

    /**
     * Permanent Delete
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->paymentService->forceDelete($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment permanently deleted successfully.',
        ]);
    }

    /**
     * Change Status
     */
    public function changeStatus(
        ChangePaymentStatusRequest $request,
        int $id
    ): JsonResponse {

        $payment = $this->paymentService->changeStatus(
            $id,
            $request->validated()['status']
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully.',
            'data' => new PaymentResource($payment),
        ]);
    }
}
