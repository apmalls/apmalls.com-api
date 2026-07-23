<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\POS\CashInRequest;
use App\Http\Requests\Admin\POS\CashOutRequest;
use App\Http\Requests\Admin\POS\CheckoutRequest;
use App\Http\Requests\Admin\POS\CloseSessionRequest;
use App\Http\Requests\Admin\POS\OpenSessionRequest;
use App\Http\Requests\Admin\POS\StorePosHoldRequest;
use App\Http\Requests\Admin\POS\UpdatePosHoldRequest;
use App\Http\Resources\POS\CashRegisterSessionResource;
use App\Http\Resources\POS\POSCheckoutResource;
use App\Http\Resources\POS\POSDashboardResource;
use App\Http\Resources\POS\PosHoldResource;
use App\Http\Resources\POS\ProductResource;
use App\Services\Contracts\POSServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POSController extends Controller
{
    public function __construct(
        protected POSServiceInterface $service
    ) {
    }

    /**
     * Open Register Session
     */
    public function openSession(OpenSessionRequest $request): JsonResponse
    {
        $session = $this->service->openSession(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Register opened successfully.',
            'data' => new CashRegisterSessionResource($session),
        ]);
    }

    /**
     * Close Register Session
     */
    public function closeSession(
        CloseSessionRequest $request,
        int $id
    ): JsonResponse {

        $session = $this->service->closeSession(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Register closed successfully.',
            'data' => new CashRegisterSessionResource($session),
        ]);
    }

    /**
     * Hold POS Bill
     */
    public function hold(
        StorePosHoldRequest $request
    ): JsonResponse {

        $hold = $this->service->createHold(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Sale placed on hold.',
            'data' => new PosHoldResource($hold),
        ]);
    }

    /**
     * Update Hold
     */
    public function updateHold(
        UpdatePosHoldRequest $request,
        int $id
    ): JsonResponse {

        $hold = $this->service->updateHold(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Hold updated successfully.',
            'data' => new PosHoldResource($hold),
        ]);
    }

    /**
     * Recall Hold
     */
    public function recall(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new PosHoldResource(
                $this->service->recallHold($id)
            ),
        ]);
    }

    /**
     * Cancel Hold
     */
    public function cancel(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Hold cancelled successfully.',
            'data' => new PosHoldResource(
                $this->service->cancelHold($id)
            ),
        ]);
    }

    /**
     * Barcode Scan
     */
    public function barcode(string $barcode): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->barcode($barcode),
        ]);
    }

    /**
     * Product Search
     */
    public function search(Request $request): JsonResponse
    {
        $products = $this->service->searchProduct(
            $request->input('keyword')
        );

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }

    /**
     * Checkout
     */
    public function checkout(
        CheckoutRequest $request
    ): JsonResponse {

        return response()->json([
            'success' => true,
            'data' => new POSCheckoutResource(
                $this->service->checkout(
                    $request->validated()
                )
            ),
        ]);
    }

    /**
     * Cash In
     */
    public function cashIn(
        CashInRequest $request
    ): JsonResponse {

        return response()->json([
            'success' => true,
            'message' => 'Cash received successfully.',
            'data' => $this->service->cashIn(
                $request->validated()
            ),
        ]);
    }

    /**
     * Cash Out
     */
    public function cashOut(
        CashOutRequest $request
    ): JsonResponse {

        return response()->json([
            'success' => true,
            'message' => 'Cash paid successfully.',
            'data' => $this->service->cashOut(
                $request->validated()
            ),
        ]);
    }

    /**
     * Session Summary
     */
    public function summary(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->sessionSummary($id),
        ]);
    }

    /**
     * POS Dashboard
     */
    public function dashboard(): JsonResponse
    {
        return response()->json([

            'success' => true,

            'message' => 'POS dashboard fetched successfully.',

            'data' => new POSDashboardResource(

                $this->service->dashboard()

            )

        ]);
    }
}
