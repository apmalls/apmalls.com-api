<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\PaymentService;
use App\Http\Requests\Website\Payment\MakePaymentRequest;

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
}
