<?php

namespace App\Repositories\Contracts;

use App\Models\Purchase\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Common Listing (Admin + Website)
    |--------------------------------------------------------------------------
    */

    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator;

    public function all(array $filters = []): Collection;

    public function trashedPaginate(
        int $perPage = 15
    ): LengthAwarePaginator;

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(int $id): ?PurchaseOrder;

    public function findOrFail(int $id): PurchaseOrder;

    public function findByPurchaseNo(string $purchaseNo): ?PurchaseOrder;

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(array $data): PurchaseOrder;

    public function update(
        int $id,
        array $data
    ): PurchaseOrder;

    public function delete(int $id): bool;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        int $id,
        string $status
    ): PurchaseOrder;

    /*
    |--------------------------------------------------------------------------
    | Dashboard / Reports
    |--------------------------------------------------------------------------
    */

    public function count(array $filters = []): int;

    public function totalAmount(array $filters = []): float;

    /*
    |--------------------------------------------------------------------------
    | Website + Admin Filter Support
    |--------------------------------------------------------------------------
    */

    public function filter(array $filters = []): Collection;
}
