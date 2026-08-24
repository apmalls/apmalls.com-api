<?php

namespace App\Services\Contracts;

use App\Models\Purchase\PurchaseReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseReturnServiceInterface
{
    /**
     * Paginated listing.
     */
    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Trashed listing.
     */
    public function trashedPaginate(
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * Get all purchase returns.
     */
    public function all(
        array $filters = []
    ): Collection;

    /**
     * Find purchase return.
     */
    public function find(
        int $id
    ): ?PurchaseReturn;

    /**
     * Find purchase return or fail.
     */
    public function findOrFail(
        int $id
    ): PurchaseReturn;

    /**
     * Create purchase return.
     */
    public function create(
        array $data
    ): PurchaseReturn;

    /**
     * Update purchase return.
     */
    public function update(
        int $id,
        array $data
    ): PurchaseReturn;

    /**
     * Soft delete purchase return.
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Restore purchase return.
     */
    public function restore(
        int $id
    ): bool;

    /**
     * Permanently delete purchase return.
     */
    public function forceDelete(
        int $id
    ): bool;

    /**
     * Change purchase return status.
     */
    public function changeStatus(
        int $id,
        string $status
    ): PurchaseReturn;

    /**
     * Total purchase returns.
     */
    public function count(
        array $filters = []
    ): int;

    /**
     * Total returned amount.
     */
    public function totalAmount(
        array $filters = []
    ): float|int;
}
