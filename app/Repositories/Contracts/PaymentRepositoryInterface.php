<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Payment\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface
{
    /**
     * Get paginated records.
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    /**
     * Get trashed records.
     */
    public function trash(array $filters = []): LengthAwarePaginator;

    /**
     * Get all records.
     */
    public function all(): Collection;

    /**
     * Find record.
     */
    public function find(int $id): Payment;

    /**
     * Find deleted record.
     */
    public function findWithTrashed(int $id): Payment;

    /**
     * Create record.
     */
    public function create(array $data): Payment;

    /**
     * Update record.
     */
    public function update(
        Payment $payment,
        array $data
    ): Payment;

    /**
     * Delete record.
     */
    public function delete(Payment $payment): bool;

    /**
     * Restore record.
     */
    public function restore(int $id): bool;

    /**
     * Force delete record.
     */
    public function forceDelete(int $id): bool;

    /**
     * Total completed payment.
     */
    public function getCompletedAmount(
        string $module,
        int $moduleId
    ): float;

    /**
     * Module payment history.
     */
    public function getModulePayments(
        string $module,
        int $moduleId
    ): Collection;
}
