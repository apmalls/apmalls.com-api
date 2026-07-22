<?php

namespace App\Services\Contracts;

use App\Models\Purchase\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseServiceInterface
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
    ): ?PurchaseOrder;

    public function findOrFail(
        int $id
    ): PurchaseOrder;

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): PurchaseOrder;

    public function update(
        int $id,
        array $data
    ): PurchaseOrder;

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
    ): PurchaseOrder;

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
