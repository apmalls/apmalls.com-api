<?php

namespace App\Repositories\POS;

use App\Models\POS\CashRegisterSession;
use App\Repositories\Contracts\CashRegisterSessionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CashRegisterSessionRepository implements CashRegisterSessionRepositoryInterface
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

    public function trashedPaginate(
        int $perPage = 15
    ): LengthAwarePaginator {

        return CashRegisterSession::onlyTrashed()
            ->with([
                'register',
                'cashier',
                'creator',
                'updater',
            ])
            ->latest()
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
    ): ?CashRegisterSession {

        return CashRegisterSession::with([
            'register',
            'cashier',
            'creator',
            'updater',
        ])->find($id);
    }

    public function findOrFail(
        int $id
    ): CashRegisterSession {

        return CashRegisterSession::with([
            'register',
            'cashier',
            'transactions',
            'holds',
            'creator',
            'updater',
        ])->findOrFail($id);
    }

    public function findBySessionNo(
        string $sessionNo
    ): ?CashRegisterSession {

        return CashRegisterSession::where(
            'session_no',
            $sessionNo
        )->first();
    }

    public function findOpenSession(
        int $cashRegisterId,
        int $userId
    ): ?CashRegisterSession {

        return CashRegisterSession::with([
                'register',
                'cashier',
            ])
            ->where('cash_register_id', $cashRegisterId)
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'status',
                CashRegisterSession::STATUS_OPEN
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): CashRegisterSession {

        return CashRegisterSession::create($data);
    }

    public function update(
        int $id,
        array $data
    ): CashRegisterSession {

        $session = $this->findOrFail($id);

        $session->update($data);

        return $session->fresh();
    }

    public function delete(
        int $id
    ): bool {

        return $this->findOrFail($id)
            ->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Trash
    |--------------------------------------------------------------------------
    */

    public function restore(
        int $id
    ): bool {

        return CashRegisterSession::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(
        int $id
    ): bool {

        return CashRegisterSession::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();
    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        int $id,
        string $status
    ): CashRegisterSession {

        $session = $this->findOrFail($id);

        $session->update([
            'status' => $status,
        ]);

        return $session->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function count(
        array $filters = []
    ): int {

        return $this->filter($filters)
            ->count();
    }

    public function totalOpeningBalance(
        array $filters = []
    ): float {

        return (float) $this->filter($filters)
            ->sum('opening_balance');
    }

    public function totalClosingBalance(
        array $filters = []
    ): float {

        return (float) $this->filter($filters)
            ->sum('closing_balance');
    }

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    protected function filter(
        array $filters = []
    ) {

        return CashRegisterSession::query()

            ->with([
                'register',
                'cashier',
                'creator',
                'updater',
            ])

            ->when(
                $filters['status'] ?? null,
                fn($q, $status) => $q->where(
                    'status',
                    $status
                )
            )

            ->when(
                $filters['cash_register_id'] ?? null,
                fn($q, $register) => $q->where(
                    'cash_register_id',
                    $register
                )
            )

            ->when(
                $filters['user_id'] ?? null,
                fn($q, $user) => $q->where(
                    'user_id',
                    $user
                )
            )

            ->when(
                $filters['session_no'] ?? null,
                fn($q, $sessionNo) => $q->where(
                    'session_no',
                    'ILIKE',
                    "%{$sessionNo}%"
                )
            )

            ->when(
                $filters['from_date'] ?? null,
                fn($q, $date) => $q->whereDate(
                    'opened_at',
                    '>=',
                    $date
                )
            )

            ->when(
                $filters['to_date'] ?? null,
                fn($q, $date) => $q->whereDate(
                    'opened_at',
                    '<=',
                    $date
                )
            )

            ->latest();
    }
}
