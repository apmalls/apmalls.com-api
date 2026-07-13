<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\POS\CashRegister;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CashRegisterRepositoryInterface
{
    /**
     * Get paginated registers.
     */
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Get trashed registers.
     */
    public function trash(
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Get all registers.
     */
    public function all(): Collection;

    /**
     * Find register.
     */
    public function find(
        int $id
    ): CashRegister;

    /**
     * Find deleted register.
     */
    public function findWithTrashed(
        int $id
    ): CashRegister;

    /**
     * Find open register by user.
     */
    public function findOpenRegisterByUser(
        int $userId
    ): ?CashRegister;

    /**
     * Create register.
     */
    public function create(
        array $data
    ): CashRegister;

    /**
     * Update register.
     */
    public function update(
        CashRegister $cashRegister,
        array $data
    ): CashRegister;

    /**
     * Delete register.
     */
    public function delete(
        CashRegister $cashRegister
    ): bool;

    /**
     * Restore register.
     */
    public function restore(
        int $id
    ): bool;

    /**
     * Permanently delete register.
     */
    public function forceDelete(
        int $id
    ): bool;
}
