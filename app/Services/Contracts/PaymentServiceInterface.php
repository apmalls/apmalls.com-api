<?php

namespace App\Services\Contracts;


use App\Models\Payment\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentServiceInterface
{
    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function paginate(array $filters = []): LengthAwarePaginator;

    public function trashedPaginate(array $filters = []): LengthAwarePaginator;

    public function all(): Collection;

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(int $id): Payment;

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Payment;

    public function update(int $id, array $data): Payment;

    public function delete(int $id): bool;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(int $id, string $status): Payment;

    /*
    |--------------------------------------------------------------------------
    | Payment Operations
    |--------------------------------------------------------------------------
    */

    public function pay(array $data): Payment;

    public function refund(int $id, array $data = []): Payment;

    public function verifyPayment(int $id): Payment;

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
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

    public function createPurchasePayment(
        int $purchaseId,
        array $data
    ): Payment;

    public function createSalePayment(
        int $saleId,
        array $data
    ): Payment;

    public function createAdvancePayment(
        array $data
    ): Payment;

    public function createGatewayOrder(
        int $paymentId
    ): array;

    public function verifyGatewayPayment(
        array $payload
    ): Payment;

    public function webhook(
        array $payload
    ): bool;
}
