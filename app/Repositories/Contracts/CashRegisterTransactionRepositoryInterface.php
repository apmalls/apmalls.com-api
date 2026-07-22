<?php

namespace App\Repositories\Contracts;

use App\Models\POS\CashRegisterTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CashRegisterTransactionRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator;

    public function all(
        array $filters = []
    ): Collection;

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id
    ): ?CashRegisterTransaction;

    public function findOrFail(
        int $id
    ): CashRegisterTransaction;

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): CashRegisterTransaction;

    public function update(
        int $id,
        array $data
    ): CashRegisterTransaction;

    public function delete(
        int $id
    ): bool;

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function totalCashIn(
        int $sessionId
    ): float;

    public function totalCashOut(
        int $sessionId
    ): float;

    public function sessionTransactions(
        int $sessionId
    ): Collection;

    public function filter(
        array $filters = []
    );

    public function totalCashSale(
        int $sessionId
    ): float;

}
