<?php

namespace App\Repositories\Contracts;

use App\Models\Payment\PaymentGatewayTransaction;

interface PaymentGatewayTransactionRepositoryInterface
{
    public function find(int $id): ?PaymentGatewayTransaction;

    public function findByGatewayOrderId(string $gatewayOrderId): ?PaymentGatewayTransaction;

    public function create(array $data): PaymentGatewayTransaction;

    public function update(int $id,array $data): PaymentGatewayTransaction;




}
