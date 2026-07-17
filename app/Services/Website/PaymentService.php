<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Payment\Payment;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\SaleOrder;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\PaymentModeRepositoryInterface;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;

class PaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected PaymentModeRepositoryInterface $paymentModeRepository,
        protected SaleOrderRepositoryInterface $saleOrderRepository,
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
}
