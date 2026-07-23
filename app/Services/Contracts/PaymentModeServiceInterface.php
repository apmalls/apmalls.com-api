<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Payment\PaymentMode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentModeServiceInterface
{
    /**
     * Display a listing of payment modes.
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    /**
     * Display trashed payment modes.
     */
    public function trashedPaginate(array $filters = []): LengthAwarePaginator;

    /**
     * Get all payment modes.
     */
    public function all(): Collection;

    /**
     * Find payment mode by id.
     */
    public function find(int $id): PaymentMode;

    /**
     * Create a payment mode.
     */
    public function create(array $data): PaymentMode;

    /**
     * Update a payment mode.
     */
    public function update(int $id, array $data): PaymentMode;

    /**
     * Delete a payment mode.
     */
    public function delete(int $id): bool;

    /**
     * Restore a soft deleted payment mode.
     */
    public function restore(int $id): bool;

    /**
     * Force delete a payment mode.
     */
    public function forceDelete(int $id): bool;

    /**
     * Get active payment modes.
     */
    public function active(): Collection;
}
