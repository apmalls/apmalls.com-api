<?php

namespace App\Repositories\Contracts;

use App\Models\Sale\SaleReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SaleReturnRepositoryInterface
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
    ): ?SaleReturn;

    public function findOrFail(
        int $id
    ): SaleReturn;

    public function findByReturnNo(
        string $returnNo
    ): ?SaleReturn;

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): SaleReturn;

    public function update(
        int $id,
        array $data
    ): SaleReturn;

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
    ): SaleReturn;

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function count(
        array $filters = []
    ): int;

    public function totalAmount(
        array $filters = []
    ): float;
}
