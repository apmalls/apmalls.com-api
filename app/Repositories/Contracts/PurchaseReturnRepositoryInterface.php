<?php

namespace App\Repositories\Contracts;

use App\Models\Purchase\PurchaseReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseReturnRepositoryInterface
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
     * Find or fail.
     */
    public function findOrFail(
        int $id
    ): PurchaseReturn;

    /**
     * Find by return number.
     */
    public function findByReturnNo(
        string $returnNo
    ): ?PurchaseReturn;

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
     * Soft delete.
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Restore.
     */
    public function restore(
        int $id
    ): bool;

    /**
     * Force delete.
     */
    public function forceDelete(
        int $id
    ): bool;

    /**
     * Change status.
     */
    public function changeStatus(
        int $id,
        string $status
    ): PurchaseReturn;

    /**
     * Count purchase returns.
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
