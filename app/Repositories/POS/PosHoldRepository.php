<?php

namespace App\Repositories\POS;

use App\Models\POS\PosHold;
use App\Repositories\Contracts\PosHoldRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PosHoldRepository implements PosHoldRepositoryInterface
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

        return PosHold::onlyTrashed()
            ->with([
                'customer',
                'session',
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
    ): ?PosHold {

        return PosHold::with([
            'customer',
            'session',
            'creator',
            'updater',
        ])->find($id);
    }

    public function findOrFail(
        int $id
    ): PosHold {

        return PosHold::with([
            'customer',
            'session',
            'items.product',
            'creator',
            'updater',
        ])->findOrFail($id);
    }

    public function findByHoldNo(
        string $holdNo
    ): ?PosHold {

        return PosHold::where(
            'hold_no',
            $holdNo
        )->first();
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): PosHold {

        return PosHold::create($data);
    }

    public function update(
        int $id,
        array $data
    ): PosHold {

        $hold = $this->findOrFail($id);

        $hold->update($data);

        return $hold->fresh();
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

        return PosHold::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(
        int $id
    ): bool {

        return PosHold::onlyTrashed()
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
    ): PosHold {

        $hold = $this->findOrFail($id);

        $hold->update([
            'status' => $status,
        ]);

        return $hold->fresh();
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

    public function totalAmount(
        array $filters = []
    ): float {

        return (float) $this->filter($filters)
            ->sum('grand_total');
    }

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    protected function filter(
        array $filters = []
    ) {

        return PosHold::query()

            ->with([
                'customer',
                'session',
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
                $filters['customer_id'] ?? null,
                fn($q, $customer) => $q->where(
                    'customer_id',
                    $customer
                )
            )

            ->when(
                $filters['cash_register_session_id'] ?? null,
                fn($q, $session) => $q->where(
                    'cash_register_session_id',
                    $session
                )
            )

            ->when(
                $filters['hold_no'] ?? null,
                fn($q, $holdNo) => $q->where(
                    'hold_no',
                    'ILIKE',
                    "%{$holdNo}%"
                )
            )

            ->when(
                $filters['from_date'] ?? null,
                fn($q, $date) => $q->whereDate(
                    'created_at',
                    '>=',
                    $date
                )
            )

            ->when(
                $filters['to_date'] ?? null,
                fn($q, $date) => $q->whereDate(
                    'created_at',
                    '<=',
                    $date
                )
            )

            ->latest();
    }

    public function recall(
        int $id
    ): PosHold {
        return $this->findOrFail($id);
    }

    public function cancel(
        int $id
    ): PosHold {
        return $this->changeStatus(
            $id,
            PosHold::STATUS_CANCELLED
        );
    }

    public function complete(
        int $id
    ): PosHold {
        return $this->changeStatus(
            $id,
            PosHold::STATUS_COMPLETED
        );
    }
}
