<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Payment\Payment;
use App\Repositories\Contracts\PaymentGatewayTransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\SaleOrder;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\PaymentModeRepositoryInterface;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;
use Razorpay\Api\Api;
use Illuminate\Validation\ValidationException;
use Razorpay\Api\Errors\SignatureVerificationError;


class PaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected PaymentModeRepositoryInterface $paymentModeRepository,
        protected SaleOrderRepositoryInterface $saleOrderRepository,
        protected PaymentGatewayTransactionRepositoryInterface $gatewayRepository,
    ) {
    }

    /**
     * Active Payment Modes
     */
    public function paymentModes()
    {
        return $this->paymentModeRepository
            ->active();
    }

    /**
     * Make Payment
     */
    public function pay(
        int $orderId,
        array $data
    ): Payment {

        return DB::transaction(function () use ($orderId, $data) {

            $order = $this->saleOrderRepository
                ->find($orderId);

            $paymentMode = $this->paymentModeRepository
                ->find($data['payment_mode_id']);

            $payment = $this->paymentRepository
                ->create([

                    'payment_no' => $this->paymentNo(),

                    'payment_date' => now(),

                    'module' => 'sale',

                    'module_id' => $order->id,

                    'payment_mode_id' => $paymentMode->id,

                    'amount' => $data['amount'],

                    'transaction_no' => $data['transaction_no'] ?? null,

                    'reference_no' => $data['reference_no'] ?? null,

                    'status' => 'Completed',

                    'remarks' => $data['remarks'] ?? null,

                    'created_by' => auth()->id(),

                ]);

            $paid = $order->paid_amount + $payment->amount;

            $due = $order->grand_total - $paid;

            $this->saleOrderRepository
                ->update($order->id, [

                    'paid_amount' => $paid,

                    'due_amount' => max($due, 0),

                    'status' => $due <= 0
                        ? 'Completed'
                        : 'Confirmed',

                ]);

            return $payment->fresh([
                'paymentMode',
            ]);

        });

    }

    /**
     * Generate Payment Number
     */
    private function paymentNo(): string
    {
        return 'PAY-' . str_pad(

            (string) (
                Payment::max('id') + 1
            ),

            6,

            '0',

            STR_PAD_LEFT

        );
    }

    /**
     * Create Razorpay Order
     */
    public function createRazorpayOrder(
        int $customerId,
        int $orderId
    ): array {

        $order = $this->saleOrderRepository->find($orderId);

        if (!$order) {
            throw ValidationException::withMessages([
                'order' => 'Order not found.',
            ]);
        }

        if ($order->customer_id !== $customerId) {
            throw ValidationException::withMessages([
                'order' => 'Unauthorized order.',
            ]);
        }

        if ($order->due_amount <= 0) {
            throw ValidationException::withMessages([
                'payment' => 'Order already paid.',
            ]);
        }

        $api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );

        $razorpayOrder = $api->orders->create([

            'receipt' => $order->sale_no,

            'amount' => (int) ($order->due_amount * 100),

            'currency' => 'INR',

        ]);

        $this->gatewayRepository->create([

            'sale_order_id' => $order->id,

            'payment_mode_id' => $this->paymentModeRepository
                ->findByCode('RAZORPAY')
                ->id,

            'gateway' => 'razorpay',

            'gateway_order_id' => $razorpayOrder['id'],

            'gateway_status' => 'created',

            'amount' => $order->due_amount,

            'currency' => 'INR',

            'request_payload' => [

                'receipt' => $order->sale_no,

            ],

            'response_payload' => $razorpayOrder->toArray(),

        ]);

        return [

            'key' => config('services.razorpay.key'),

            'order_id' => $order->id,

            'razorpay_order_id' => $razorpayOrder['id'],

            'amount' => $razorpayOrder['amount'],

            'currency' => $razorpayOrder['currency'],

            'customer' => [

                'name' => auth()->user()->full_name,

                'email' => auth()->user()->email,

                'contact' => auth()->user()->mobile,

            ],

        ];
    }

    /**
     * Verify Razorpay Payment
     */
    public function verifyRazorpayPayment(
        int $customerId,
        array $data
    ): Payment {

        return DB::transaction(function () use ($customerId, $data) {

            $gateway = $this->gatewayRepository
                ->findByGatewayOrderId(
                    $data['razorpay_order_id']
                );

            if (!$gateway) {

                throw ValidationException::withMessages([

                    'payment' => 'Transaction not found.',

                ]);

            }

            $order = $gateway->saleOrder;

            if ($order->customer_id != $customerId) {

                throw ValidationException::withMessages([

                    'payment' => 'Unauthorized.',

                ]);

            }

            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            $api->utility->verifyPaymentSignature([

                'razorpay_order_id' => $data['razorpay_order_id'],

                'razorpay_payment_id' => $data['razorpay_payment_id'],

                'razorpay_signature' => $data['razorpay_signature'],

            ]);

            $payment = $this->paymentRepository->create([

                'payment_no' => $this->paymentNo(),

                'payment_date' => now(),

                'module' => 'sale',

                'module_id' => $order->id,

                'payment_mode_id' => $gateway->payment_mode_id,

                'amount' => $gateway->amount,

                'transaction_no' => $data['razorpay_payment_id'],

                'reference_no' => $data['razorpay_order_id'],

                'status' => 'Completed',

                'remarks' => 'Paid via Razorpay',

                'created_by' => auth()->id(),

            ]);

            $this->gatewayRepository->update(
                $gateway->id,
                [

                    'payment_id' => $payment->id,

                    'gateway_payment_id' => $data['razorpay_payment_id'],

                    'gateway_signature' => $data['razorpay_signature'],

                    'gateway_status' => 'captured',

                    'paid_at' => now(),

                ]
            );

            $this->saleOrderRepository->update(
                $order->id,
                [

                    'paid_amount' => $order->grand_total,

                    'due_amount' => 0,

                    'status' => 'Completed',

                ]
            );

            return $payment->fresh('paymentMode');

        });

    }

    public function razorpayWebhook(
        array $payload
    ): array {
        // payment.captured

        // payment.failed

        // refund.created

        return [
            'success' => true,
        ];
    }
}
