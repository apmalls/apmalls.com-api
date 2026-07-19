<?php

namespace App\Repositories\Payment;

use App\Models\Payment\PaymentGatewayTransaction;
use App\Repositories\Contracts\PaymentGatewayTransactionRepositoryInterface;

class PaymentGatewayTransactionRepository implements PaymentGatewayTransactionRepositoryInterface
{
    public function find(int $id): ?PaymentGatewayTransaction
    {
        return PaymentGatewayTransaction::find($id);
    }

    public function findByGatewayOrderId(
        string $gatewayOrderId
    ): ?PaymentGatewayTransaction {

        return PaymentGatewayTransaction::with([
            'saleOrder',
            'paymentMode',
        ])
            ->where(
                'gateway_order_id',
                $gatewayOrderId
            )
            ->first();
    }

    public function create(array $data): PaymentGatewayTransaction
    {
        return PaymentGatewayTransaction::create($data);
    }

    public function update(
        int $id,
        array $data
    ): PaymentGatewayTransaction {

        $transaction = $this->find($id);

        $transaction->update($data);

        return $transaction->fresh();
    }


}
