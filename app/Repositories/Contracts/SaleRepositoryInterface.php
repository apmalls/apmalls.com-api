<?php

namespace App\Repositories\Contracts;

use App\Models\Sale\SaleOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SaleRepositoryInterface
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
    ): ?SaleOrder;

    public function findOrFail(
        int $id
    ): SaleOrder;

    public function findBySaleNo(
        string $saleNo
    ): ?SaleOrder;

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): SaleOrder;

    public function update(
        int $id,
        array $data
    ): SaleOrder;

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
    ): SaleOrder;

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

    public function recent(
        int $limit = 10
    ): Collection;
}
