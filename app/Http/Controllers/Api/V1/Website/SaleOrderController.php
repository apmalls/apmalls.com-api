<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\SaleOrderService;
use App\Http\Requests\Website\Sale\PlaceOrderRequest;

class SaleOrderController extends Controller
{
    public function __construct(
        protected SaleOrderService $saleOrderService,
    ) {
    }

    /**
     * Place Order
     */
    public function placeOrder(
        PlaceOrderRequest $request
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            $order = $this->saleOrderService
                ->placeOrder(

                    $customerId,

                    $request->validated()

                );

            return response()->json([

                'success' => true,

                'message' => 'Order placed successfully.',

                'data' => $order,

            ], 201);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * My Orders
     */
    public function index(): JsonResponse
    {
        try {

            $customerId = auth()->user()->customer->id;

            return response()->json([

                'success' => true,

                'message' => 'Orders fetched successfully.',

                'data' => $this->saleOrderService
                    ->index($customerId),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Order Details
     */
    public function show(
        int $id
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            Log::info('Invoice Show', [
    'customer_id' => $customerId,
    'order_id' => $id,
]);

            return response()->json([

                'success' => true,

                'message' => 'Order fetched successfully.',

                'data' => $this->saleOrderService
                    ->show(

                        $customerId,

                        $id

                    ),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Cancel Order
     */
    public function cancel(
        int $id
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            $order = $this->saleOrderService
                ->cancel(

                    $customerId,

                    $id

                );

            return response()->json([

                'success' => true,

                'message' => 'Order cancelled successfully.',

                'data' => $order,

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Download Invoice
     */
    // public function downloadInvoice(
    //     int $id
    // ) {
    //     try {

    //         $customerId = auth()->user()->customer->id;

    //         return $this->saleOrderService
    //             ->downloadInvoice(
    //                 $customerId,
    //                 $id
    //             );

    //     } catch (Throwable $exception) {

    //         return $this->handleException($exception);

    //     }
    // }

    public function downloadInvoice(
    int $id
) {
    try {

        Log::info('Invoice API called', [
            'order_id' => $id,
            'user' => auth()->user(),
        ]);

        Log::info('Customer Relation', [
            'customer' => auth()->user()->customer,
        ]);

        $customerId = auth()->user()->customer->id;

        Log::info('Customer ID', [
            'customer_id' => $customerId,
        ]);

        return $this->saleOrderService
            ->downloadInvoice(
                $customerId,
                $id
            );

    } catch (Throwable $exception) {

        Log::error('Invoice Download Error', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        return $this->handleException($exception);

    }
}
}
