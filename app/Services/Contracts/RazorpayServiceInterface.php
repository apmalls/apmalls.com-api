<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface RazorpayServiceInterface
{
    /**
     * Create Razorpay Order.
     */
    public function createOrder(
        array $data
    ): array;

    /**
     * Verify Payment Signature.
     */
    public function verifySignature(
        array $payload
    ): bool;

    /**
     * Fetch Payment.
     */
    public function fetchPayment(
        string $paymentId
    ): array;

    /**
     * Verify Webhook Signature.
     */
    public function verifyWebhook(
        string $payload,
        string $signature
    ): bool;
}
