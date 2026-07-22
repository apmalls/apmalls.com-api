<?php

declare(strict_types=1);

namespace App\Repositories\Payment;

use App\Models\Payment\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Payment::query()

            ->with([
                'paymentMode:id,name',
                'customer:id,name',
                'supplier:id,name'
            ])

            ->when(
                $filters['search'] ?? null,
                function ($query, $search) {

                    $query->where(function ($q) use ($search) {

                        $q->where('payment_no', 'ILIKE', "%{$search}%")
                            ->orWhere('transaction_no', 'ILIKE', "%{$search}%")
                            ->orWhere('reference_no', 'ILIKE', "%{$search}%");

                    });

                }
            )

            ->when(
                $filters['status'] ?? null,
                fn($q, $status) => $q->where('status', $status)
            )

            ->when(
                $filters['payment_mode_id'] ?? null,
                fn($q, $mode) => $q->where('payment_mode_id', $mode)
            )

            ->latest('id')

            ->paginate(
                $filters['per_page'] ?? config('constant.pagination_count')
            );
    }

    public function trashedPaginate(array $filters = []): LengthAwarePaginator
    {
        return Payment::onlyTrashed()

            ->latest('id')

            ->paginate(
                $filters['per_page'] ?? config('constant.pagination_count')
            );
    }

    public function all(): Collection
    {
        return Payment::latest('id')->get();
    }

    public function find(int $id): ?Payment
    {
        return Payment::find($id);
    }

    public function findOrFail(int $id): Payment
    {
        return Payment::findOrFail($id);
    }

    public function getByPaymentNo(string $paymentNo): ?Payment
    {
        return Payment::where('payment_no', $paymentNo)->first();
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function update(int $id, array $data): Payment
    {
        $payment = Payment::findOrFail($id);

        $payment->update($data);

        return $payment->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) Payment::findOrFail($id)->delete();
    }

    public function restore(int $id): bool
    {
        return (bool) Payment::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) Payment::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();
    }

    public function changeStatus(int $id, string $status): Payment
    {
        $payment = Payment::findOrFail($id);

        $payment->update([
            'status' => $status,
        ]);

        return $payment->refresh();
    }

    public function exists(int $id): bool
    {
        return Payment::whereKey($id)->exists();
    }

    public function existsByPaymentNo(string $paymentNo): bool
    {
        return Payment::where('payment_no', $paymentNo)->exists();
    }

    public function existsByTransactionNo(string $transactionNo): bool
    {
        return Payment::where('transaction_no', $transactionNo)->exists();
    }

    public function findByPaymentable(
        string $paymentableType,
        int $paymentableId
    ): Collection {

        return Payment::where('paymentable_type', $paymentableType)
            ->where('paymentable_id', $paymentableId)
            ->get();
    }

    public function totalPaid(
        string $paymentableType,
        int $paymentableId
    ): float {

        return (float) Payment::where('paymentable_type', $paymentableType)
            ->where('paymentable_id', $paymentableId)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');
    }

    public function completedPayments(): Collection
    {
        return Payment::completed()->get();
    }

    public function pendingPayments(): Collection
    {
        return Payment::pending()->get();
    }

    public function failedPayments(): Collection
    {
        return Payment::failed()->get();
    }

    public function refundedPayments(): Collection
    {
        return Payment::where('status', Payment::STATUS_REFUNDED)->get();
    }

    public function cancelledPayments(): Collection
    {
        return Payment::where('status', Payment::STATUS_CANCELLED)->get();
    }

    public function todayPayments(): Collection
    {
        return Payment::whereDate('payment_date', today())->get();
    }

    public function betweenDates(
        string $fromDate,
        string $toDate
    ): Collection {

        return Payment::whereBetween(
            'payment_date',
            [$fromDate, $toDate]
        )->get();
    }
}
