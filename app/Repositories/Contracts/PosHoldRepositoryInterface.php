<?php

namespace App\Repositories\Contracts;

use App\Models\POS\PosHold;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PosHoldRepositoryInterface
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
    ): ?PosHold;

    public function findOrFail(
        int $id
    ): PosHold;

    public function findByHoldNo(
        string $holdNo
    ): ?PosHold;

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): PosHold;

    public function update(
        int $id,
        array $data
    ): PosHold;

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
    ): PosHold;

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function count(
        array $filters = []
    ): int;

    public function holdCount(): int;

    public function totalAmount(
        array $filters = []
    ): float;

    public function recall(
        int $id
    ): PosHold;

    public function cancel(
        int $id
    ): PosHold;

    public function complete(
        int $id
    ): PosHold;
}
