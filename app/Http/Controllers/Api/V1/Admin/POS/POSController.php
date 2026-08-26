<?php

namespace App\Http\Controllers\Api\V1\Admin\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\POS\CashInRequest;
use App\Http\Requests\Admin\POS\CashOutRequest;
use App\Http\Requests\Admin\POS\CheckoutRequest;
use App\Http\Requests\Admin\POS\CloseSessionRequest;
use App\Http\Requests\Admin\POS\OpenSessionRequest;
use App\Http\Requests\Admin\POS\StorePosHoldRequest;
use App\Http\Requests\Admin\POS\UpdatePosHoldRequest;
use App\Http\Requests\Admin\POS\UpdatePosOrderRequest;
use App\Http\Resources\Payment\PaymentModeResource;
use App\Http\Resources\POS\CashRegisterResource;
use App\Http\Resources\POS\CashRegisterSessionResource;
use App\Http\Resources\POS\POSCheckoutResource;
use App\Http\Resources\POS\POSDashboardResource;
use App\Http\Resources\POS\PosHoldResource;
use App\Http\Resources\POS\ProductResource;
use App\Http\Resources\Sale\SaleResource;
use App\Services\Contracts\POSServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class POSController extends Controller
{
    public function __construct(
        protected POSServiceInterface $service
    ) {
    }

    /**
     * Cash Register Listing
     */
    public function registers(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'user_id' => $request->input('user_id'),
            'register_no' => $request->input('register_no'),
            'name' => $request->input('search', $request->input('name')),
        ];

        $registers = $this->service->registers(
            (int) $request->get('per_page', 10),
            array_filter($filters, fn($value) => $value !== null && $value !== '')
        );

        $page = $registers->toArray();
        $page['data'] = CashRegisterResource::collection(
            $registers->getCollection()
        )->resolve();

        return response()->json([
            'success' => true,
            'message' => 'Cash registers fetched successfully.',
            'data' => $page,
        ]);
    }

    /**
     * Store Cash Register
     */
    public function storeRegister(Request $request): JsonResponse
    {
        $register = $this->service->createRegister(
            $this->validatedRegisterData($request)
        );

        return response()->json([
            'success' => true,
            'message' => 'Cash register created successfully.',
            'data' => new CashRegisterResource($register->load('user')),
        ], 201);
    }

    /**
     * Show Cash Register
     */
    public function showRegister(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Cash register fetched successfully.',
            'data' => new CashRegisterResource($this->service->register($id)),
        ]);
    }

    /**
     * Update Cash Register
     */
    public function updateRegister(Request $request, int $id): JsonResponse
    {
        $register = $this->service->updateRegister(
            $id,
            $this->validatedRegisterData($request, $id)
        );

        return response()->json([
            'success' => true,
            'message' => 'Cash register updated successfully.',
            'data' => new CashRegisterResource($register->load('user')),
        ]);
    }

    /**
     * Delete Cash Register
     */
    public function deleteRegister(int $id): JsonResponse
    {
        $this->service->deleteRegister($id);

        return response()->json([
            'success' => true,
            'message' => 'Cash register deleted successfully.',
        ]);
    }

    private function validatedRegisterData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'register_no' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('cash_registers', 'register_no')->ignore($id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'closing_balance' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['Open', 'Closed'])],
            'opened_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);
    }

    /**
     * Session Context
     */
    public function sessionContext(): JsonResponse
    {
        $context = $this->service->sessionContext();

        return response()->json([
            'success' => true,
            'message' => $context['message'],
            'data' => [
                'cashier' => $context['cashier'],
                'register' => $context['register']
                    ? new CashRegisterResource($context['register'])
                    : null,
                'session' => $context['session']
                    ? new CashRegisterSessionResource($context['session'])
                    : null,
                'payment_modes' => PaymentModeResource::collection(
                    $context['payment_modes']
                ),
                'billing_allowed' => $context['billing_allowed'],
                'requires_session' => $context['requires_session'],
                'message' => $context['message'],
            ],
        ]);
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
     * Current Held Bills
     */
    public function heldBills(Request $request): JsonResponse
    {
        $holds = $this->service->currentHolds(
            (int) $request->get('per_page', 10)
        );

        $page = $holds->toArray();
        $page['data'] = PosHoldResource::collection(
            $holds->getCollection()
        )->resolve();

        return response()->json([
            'success' => true,
            'message' => 'Held bills fetched successfully.',
            'data' => $page,
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
     * POS Order Detail
     */
    public function order(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'POS order fetched successfully.',
            'data' => new SaleResource(
                $this->service->order($id)
            ),
        ]);
    }

    /**
     * Update POS Order
     */
    public function updateOrder(
        UpdatePosOrderRequest $request,
        int $id
    ): JsonResponse {

        return response()->json([
            'success' => true,
            'message' => 'POS order updated successfully.',
            'data' => new POSCheckoutResource(
                $this->service->updateOrder(
                    $id,
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
