<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use App\Services\Contracts\PaymentServiceInterface;
use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Contracts\CheckoutServiceInterface;



class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutServiceInterface $checkoutService,
        protected PaymentServiceInterface $paymentService,
    ) {
    }



    public function checkout(
        CheckoutRequest $request
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            /*
            |--------------------------------------------------------------------------
            | Create Draft Sale Order
            |--------------------------------------------------------------------------
            */

            $saleOrder = $this->checkoutService
                ->checkout(
                    $customerId,
                    $request->validated()
                );

            /*
            |--------------------------------------------------------------------------
            | Create Payment
            |--------------------------------------------------------------------------
            */

            $payment = $this->paymentService
                ->createSalePayment(

                    $saleOrder->id,

                    [

                        'amount' => $saleOrder->grand_total,

                        'payment_mode_id' => $request->payment_mode_id,

                        'status' => \App\Models\Payment\Payment::STATUS_PENDING,

                    ]

                );

            /*
            |--------------------------------------------------------------------------
            | Create Razorpay Order
            |--------------------------------------------------------------------------
            */

            $gatewayOrder = $this->paymentService
                ->createGatewayOrder(
                    $payment->id
                );

            return response()->json([

                'success' => true,

                'message' => 'Checkout created successfully.',

                'data' => [

                    'sale_order' => $saleOrder,

                    'payment' => $payment,

                    'gateway' => $gatewayOrder,

                ],

            ]);

        } catch (Throwable $exception) {

            return $this->handleException(
                $exception
            );

        }

    }

    /**
     * My Orders
     * GET /api/v1/website/checkout/orders
     */
    public function orders(): JsonResponse
    {
        try {

            $customerId = auth()->user()->customer->id;

            $perPage = request()->integer('per_page', 10);

            $orders = $this->checkoutService
                ->myOrders(
                    $customerId,
                    $perPage
                );

            return response()->json([

                'success' => true,

                'message' => 'Orders fetched successfully.',

                'data' => $orders,

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Order Details
     * GET /api/v1/website/checkout/orders/{saleNo}
     */
    public function orderDetails(
        string $saleNo
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            $order = $this->checkoutService
                ->orderDetails(
                    $customerId,
                    $saleNo
                );

            if (!$order) {

                return response()->json([

                    'success' => false,

                    'message' => 'Order not found.',

                ], 404);

            }

            return response()->json([

                'success' => true,

                'message' => 'Order fetched successfully.',

                'data' => $order,

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Cancel Order
     * POST /api/v1/website/checkout/orders/{saleNo}/cancel
     */
    public function cancelOrder(
        string $saleNo
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            $order = $this->checkoutService
                ->cancelOrder(
                    $customerId,
                    $saleNo
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
}
