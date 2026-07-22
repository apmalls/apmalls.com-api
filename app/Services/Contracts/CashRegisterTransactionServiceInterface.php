<?php

namespace App\Services\Contracts;

use App\Models\POS\CashRegisterTransaction;

interface CashRegisterTransactionServiceInterface
{
    public function cashIn(array $data): CashRegisterTransaction;

    public function cashOut(array $data): CashRegisterTransaction;

    public function create(array $data): CashRegisterTransaction;

    public function delete(int $id): bool;
}
