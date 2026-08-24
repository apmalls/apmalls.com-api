<?php

namespace App\Repositories\POS;

use App\Models\Payment\Payment;
use App\Models\POS\CashRegisterTransaction;
use App\Repositories\Contracts\CashRegisterTransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CashRegisterTransactionRepository implements CashRegisterTransactionRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {

        return $this->filter($filters)
            ->paginate($perPage);
    }

    public function all(
        array $filters = []
    ): Collection {

        return $this->filter($filters)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id
    ): ?CashRegisterTransaction {

        return CashRegisterTransaction::with([
            'session',
            'paymentMode',
            'reference',
            'creator',
            'updater',
        ])->find($id);
    }

    public function findOrFail(
        int $id
    ): CashRegisterTransaction {

        return CashRegisterTransaction::with([
            'session',
            'paymentMode',
            'reference',
            'creator',
            'updater',
        ])->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): CashRegisterTransaction {

        return CashRegisterTransaction::create($data);
    }

    public function update(
        int $id,
        array $data
    ): CashRegisterTransaction {

        $transaction = $this->findOrFail($id);

        $transaction->update($data);

        return $transaction->fresh();
    }

    public function delete(
        int $id
    ): bool {

        return $this->findOrFail($id)
            ->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function totalCashIn(
        int $sessionId
    ): float {

        return (float) CashRegisterTransaction::query()

            ->where(
                'cash_register_session_id',
                $sessionId
            )

            ->where(
                'type',
                CashRegisterTransaction::TYPE_CASH_IN
            )

            ->sum('amount');
    }

    public function totalCashOut(
        int $sessionId
    ): float {

        return (float) CashRegisterTransaction::query()

            ->where(
                'cash_register_session_id',
                $sessionId
            )

            ->where(
                'type',
                CashRegisterTransaction::TYPE_CASH_OUT
            )

            ->sum('amount');
    }

    public function sessionTransactions(
        int $sessionId
    ): Collection {

        return CashRegisterTransaction::query()

            ->where(
                'cash_register_session_id',
                $sessionId
            )

            ->latest('transaction_at')

            ->get();
    }

    public function filter(
        array $filters = []
    ) {

        return CashRegisterTransaction::query()

            ->when(
                $filters['session_id'] ?? null,
                fn($q, $id) =>

                $q->where(
                    'cash_register_session_id',
                    $id
                )
            )

            ->when(
                $filters['payment_mode_id'] ?? null,
                fn($q, $id) =>

                $q->where(
                    'payment_mode_id',
                    $id
                )
            )

            ->when(
                $filters['type'] ?? null,
                fn($q, $type) =>

                $q->where(
                    'type',
                    $type
                )
            )

            ->when(
                $filters['date'] ?? null,
                fn($q, $date) =>

                $q->whereDate(
                    'transaction_at',
                    $date
                )
            )

            ->latest('transaction_at');
    }

    public function totalCashSale(
        int $sessionId
    ): float {

        return (float) CashRegisterTransaction::query()

            ->where('cash_register_session_id', $sessionId)

            ->where('reference_type', Payment::class)

            ->whereHas('reference.paymentMode', function ($query) {

                $query->where('type', 'cash');

            })

            ->sum('amount');
    }
}
