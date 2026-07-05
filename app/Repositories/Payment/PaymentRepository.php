<?php

declare(strict_types=1);

namespace App\Repositories\Payment;

use App\Models\Payment\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository implements PaymentRepositoryInterface
{
    /**
     * Get paginated payments.
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Payment::query()
            ->with([
                'paymentMode',
                'creator',
                'updater',
            ])
            ->when(
                !empty($filters['search']),
                function ($query) use ($filters) {

                    $query->where(function ($query) use ($filters) {

                        $query->where(
                            'payment_no',
                            'ILIKE',
                            '%' . trim($filters['search']) . '%'
                        );

                    });

                }
            )
            ->when(
                !empty($filters['module']),
                fn($query) => $query->where(
                    'module',
                    $filters['module']
                )
            )
            ->when(
                !empty($filters['status']),
                fn($query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->latest()
            ->paginate(
                $filters['per_page'] ?? 10
            );
    }

    /**
     * Get all payments.
     */
    public function all(): Collection
    {
        return Payment::with([
            'paymentMode',
            'creator',
            'updater',
        ])
            ->latest()
            ->get();
    }

    /**
     * Get trashed payments.
     */
    public function trash(
        array $filters = []
    ): LengthAwarePaginator {

        return Payment::onlyTrashed()

            ->with([
                'paymentMode',
                'creator',
                'updater',
            ])

            ->when(
                !empty($filters['search']),
                function ($query) use ($filters) {

                    $query->where(
                        'payment_no',
                        'ILIKE',
                        '%' . trim($filters['search']) . '%'
                    );

                }
            )

            ->latest('deleted_at')

            ->paginate(
                $filters['per_page'] ?? 10
            );

    }

    /**
     * Find payment by id.
     */
    public function find(int $id): Payment
    {
        return Payment::with([
            'paymentMode',
            'creator',
            'updater',
        ])
            ->findOrFail($id);
    }

    /**
     * Find deleted payment.
     */
    public function findWithTrashed(
        int $id
    ): Payment {

        return Payment::onlyTrashed()

            ->with([
                'paymentMode',
                'creator',
                'updater',
            ])

            ->findOrFail($id);

    }

    /**
     * Create payment.
     */
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    /**
     * Update payment.
     */
    public function update(
        Payment $payment,
        array $data
    ): Payment {

        $payment->update($data);

        return $payment->refresh();
    }

    /**
     * Soft delete payment.
     */
    public function delete(
        Payment $payment
    ): bool {

        return (bool) $payment->delete();

    }

    /**
     * Restore payment.
     */
    public function restore(
        int $id
    ): bool {

        return (bool) Payment::onlyTrashed()
            ->findOrFail($id)
            ->restore();

    }

    /**
     * Permanently delete payment.
     */
    public function forceDelete(
        int $id
    ): bool {

        return (bool) Payment::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();

    }

    /**
     * Get completed payment amount.
     */
    public function getCompletedAmount(
        string $module,
        int $moduleId
    ): float {

        return (float) Payment::query()

            ->where('module', $module)

            ->where('module_id', $moduleId)

            ->where('status', 'Completed')

            ->sum('amount');

    }

    /**
     * Get payment history.
     */
    public function getModulePayments(
        string $module,
        int $moduleId
    ): Collection {

        return Payment::query()

            ->with([
                'paymentMode',
                'creator',
                'updater',
            ])

            ->where('module', $module)

            ->where('module_id', $moduleId)

            ->latest()

            ->get();

    }
}
