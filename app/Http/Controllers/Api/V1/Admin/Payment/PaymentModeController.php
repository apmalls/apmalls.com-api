<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentMode\StorePaymentModeRequest;
use App\Http\Requests\Admin\PaymentMode\UpdatePaymentModeRequest;
use App\Http\Resources\Payment\PaymentModeCollection;
use App\Http\Resources\Payment\PaymentModeResource;
use App\Services\Contracts\PaymentModeServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentModeController extends Controller
{
    public function __construct(
        protected PaymentModeServiceInterface $paymentModeService
    ) {
    }

    /**
     * Display a listing.
     */
    public function index(Request $request): PaymentModeCollection
    {
        return new PaymentModeCollection(
            $this->paymentModeService->paginate($request->all())
        );
    }

    /**
     * Display trashed records.
     */
    public function trashed(Request $request): PaymentModeCollection
    {
        return new PaymentModeCollection(
            $this->paymentModeService->trashedPaginate($request->all())
        );
    }

    /**
     * Store a newly created resource.
     */
    public function store(StorePaymentModeRequest $request): JsonResponse
    {
        $paymentMode = $this->paymentModeService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment mode created successfully.',
            'data' => new PaymentModeResource($paymentMode),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $paymentMode = $this->paymentModeService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment mode details fetched successfully.',
            'data' => new PaymentModeResource($paymentMode),
        ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(
        UpdatePaymentModeRequest $request,
        int $id
    ): JsonResponse {

        $paymentMode = $this->paymentModeService->update(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment mode updated successfully.',
            'data' => new PaymentModeResource($paymentMode),
        ]);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->paymentModeService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment mode deleted successfully.',
        ]);
    }

    /**
     * Restore soft deleted resource.
     */
    public function restore(int $id): JsonResponse
    {
        $this->paymentModeService->restore($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment mode restored successfully.',
        ]);
    }

    /**
     * Permanently delete resource.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->paymentModeService->forceDelete($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment mode permanently deleted successfully.',
        ]);
    }

    /**
     * Active Payment Modes.
     */
    public function active(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Active payment modes fetched successfully.',
            'data' => PaymentModeResource::collection(
                $this->paymentModeService->active()
            ),
        ]);
    }
}
