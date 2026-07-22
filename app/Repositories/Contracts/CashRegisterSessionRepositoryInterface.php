<?php

namespace App\Repositories\Contracts;

use App\Models\POS\CashRegisterSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CashRegisterSessionRepositoryInterface
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

    public function trashedPaginate(
        int $perPage = 15
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
    ): ?CashRegisterSession;

    public function findOrFail(
        int $id
    ): CashRegisterSession;

    public function findBySessionNo(
        string $sessionNo
    ): ?CashRegisterSession;

    public function findOpenSession(
        int $cashRegisterId,
        int $userId
    ): ?CashRegisterSession;

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): CashRegisterSession;

    public function update(
        int $id,
        array $data
    ): CashRegisterSession;

    public function delete(
        int $id
    ): bool;

    /*
    |--------------------------------------------------------------------------
    | Trash
    |--------------------------------------------------------------------------
    */

    public function restore(
        int $id
    ): bool;

    public function forceDelete(
        int $id
    ): bool;

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        int $id,
        string $status
    ): CashRegisterSession;

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function count(
        array $filters = []
    ): int;

    public function totalOpeningBalance(
        array $filters = []
    ): float;

    public function totalClosingBalance(
        array $filters = []
    ): float;
}
