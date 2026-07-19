<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use App\Http\Requests\Website\Payment\CreateRazorpayOrderRequest;
use Illuminate\Http\Client\Request;
use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\PaymentService;
use App\Http\Requests\Website\Payment\MakePaymentRequest;
use App\Http\Requests\Website\Payment\VerifyRazorpayPaymentRequest;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {
    }

    /**
     * Payment Modes
     */
    public function paymentModes(): JsonResponse
    {
        try {

            return response()->json([

                'success' => true,

                'message' => 'Payment modes fetched successfully.',

                'data' => $this->paymentService
                    ->paymentModes(),

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Make Payment
     */
    public function pay(
        MakePaymentRequest $request,
        int $orderId
    ): JsonResponse {

        try {

            $payment = $this->paymentService
                ->pay(

                    $orderId,

                    $request->validated()

                );

            return response()->json([

                'success' => true,

                'message' => 'Payment completed successfully.',

                'data' => $payment,

            ], 201);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }


    /**
     * Create Razorpay Order
     */
    public function createRazorpayOrder(
        CreateRazorpayOrderRequest $request
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            $data = $this->paymentService
                ->createRazorpayOrder(

                    $customerId,

                    $request->validated()['order_id']

                );

            return response()->json([

                'success' => true,

                'message' => 'Razorpay order created successfully.',

                'data' => $data,

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }
    /**
     * Verify Razorpay Payment
     */
    public function verifyRazorpayPayment(
        VerifyRazorpayPaymentRequest $request
    ): JsonResponse {

        try {

            $customerId = auth()->user()->customer->id;

            $payment = $this->paymentService
                ->verifyRazorpayPayment(

                    $customerId,

                    $request->validated()

                );

            return response()->json([

                'success' => true,

                'message' => 'Payment verified successfully.',

                'data' => $payment,

            ]);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }

    }

    public function razorpayWebhook(Request $request): JsonResponse
    {
        return response()->json(
            $this->paymentService->razorpayWebhook(
                $request->all()
            )
        );
    }
}
