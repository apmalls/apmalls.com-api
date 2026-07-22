<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Payment\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface
{
    /**
     * Listing
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    public function trashedPaginate(array $filters = []): LengthAwarePaginator;

    public function all(): Collection;

    /**
     * Find
     */
    public function find(int $id): ?Payment;

    public function findOrFail(int $id): Payment;

    public function getByPaymentNo(string $paymentNo): ?Payment;

    /**
     * CRUD
     */
    public function create(array $data): Payment;

    public function update(int $id, array $data): Payment;

    public function delete(int $id): bool;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    /**
     * Status
     */
    public function changeStatus(int $id, string $status): Payment;

    /**
     * Exists
     */
    public function exists(int $id): bool;

    public function existsByPaymentNo(string $paymentNo): bool;

    public function existsByTransactionNo(string $transactionNo): bool;

    /**
     * Paymentable
     */
    public function findByPaymentable(
        string $paymentableType,
        int $paymentableId
    ): Collection;

    /**
     * Reports
     */
    public function totalPaid(
        string $paymentableType,
        int $paymentableId
    ): float;

    public function completedPayments(): Collection;

    public function pendingPayments(): Collection;

    public function failedPayments(): Collection;

    public function refundedPayments(): Collection;

    public function cancelledPayments(): Collection;

    public function todayPayments(): Collection;

    public function betweenDates(
        string $fromDate,
        string $toDate
    ): Collection;
}
