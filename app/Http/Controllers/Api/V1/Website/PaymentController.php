<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use App\Http\Requests\Website\Payment\VerifyPaymentRequest;
use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

use App\Models\Sale\SaleOrder;
use App\Services\Contracts\CheckoutServiceInterface;
use App\Services\Contracts\PaymentServiceInterface;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentServiceInterface $paymentService,
        protected CheckoutServiceInterface $checkoutService,
    ) {
    }

    /**
     * Verify Razorpay Payment
     */
    public function verifyPayment(
        VerifyPaymentRequest $request
    ): JsonResponse {

        try {

            /*
            |--------------------------------------------------------------------------
            | Verify Payment
            |--------------------------------------------------------------------------
            */

            $payment = $this->paymentService
                ->verifyGatewayPayment(
                    $request->validated()
                );

            /*
            |--------------------------------------------------------------------------
            | Confirm Sale Order
            |--------------------------------------------------------------------------
            */

            $saleOrder = null;

            if (

                $payment->paymentable_type ===
                SaleOrder::class

            ) {

                $saleOrder = $this->checkoutService
                    ->paymentSuccess(

                        $payment->paymentable_id,

                        [

                            'payment_id' => $payment->id,

                            'transaction_no'
                                => $payment->transaction_no,

                        ]

                    );

            }

            return response()->json([

                'success' => true,

                'message' => 'Payment verified successfully.',

                'data' => [

                    'payment' => $payment,

                    'sale_order' => $saleOrder,

                ],

            ]);

        } catch (Throwable $exception) {

            return $this->handleException(
                $exception
            );

        }

    }
}
