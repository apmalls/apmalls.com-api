<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Exception;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use App\Services\Contracts\RazorpayServiceInterface;

class RazorpayService implements RazorpayServiceInterface
{
    protected Api $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Order
    |--------------------------------------------------------------------------
    */

    public function createOrder(
        array $data
    ): array {

        $order = $this->api->order->create([

            'receipt' => $data['receipt'],

            'amount' => (int) round(
                $data['amount'] * 100
            ),

            'currency' => $data['currency'] ?? 'INR',

            'payment_capture' => 1,

            'notes' => $data['notes'] ?? [],

        ]);

        return $order->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Verify Payment Signature
    |--------------------------------------------------------------------------
    */

    public function verifySignature(
        array $payload
    ): bool {

        try {

            $this->api
                ->utility
                ->verifyPaymentSignature([

                    'razorpay_order_id'
                    => $payload['razorpay_order_id'],

                    'razorpay_payment_id'
                    => $payload['razorpay_payment_id'],

                    'razorpay_signature'
                    => $payload['razorpay_signature'],

                ]);

            return true;

        } catch (SignatureVerificationError) {

            return false;

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch Payment
    |--------------------------------------------------------------------------
    */

    public function fetchPayment(
        string $paymentId
    ): array {

        return $this->api
            ->payment
            ->fetch($paymentId)
            ->toArray();

    }

    /*
    |--------------------------------------------------------------------------
    | Verify Webhook
    |--------------------------------------------------------------------------
    */

    public function verifyWebhook(
        string $payload,
        string $signature
    ): bool {

        try {

            $this->api
                ->utility
                ->verifyWebhookSignature(

                    $payload,

                    $signature,

                    config(
                        'services.razorpay.webhook_secret'
                    )

                );

            return true;

        } catch (Exception) {

            return false;

        }

    }
}
