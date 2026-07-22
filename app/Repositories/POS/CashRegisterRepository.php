<?php

namespace App\Repositories\POS;

use App\Models\POS\CashRegister;
use App\Repositories\Contracts\CashRegisterRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CashRegisterRepository implements CashRegisterRepositoryInterface
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

        return CashRegister::onlyTrashed()
            ->with([
                'user',
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
    ): ?CashRegister {

        return CashRegister::with([
            'user',
            'creator',
            'updater',
        ])->find($id);
    }

    public function findOrFail(
        int $id
    ): CashRegister {

        return CashRegister::with([
            'user',
            'sessions',
            'creator',
            'updater',
        ])->findOrFail($id);
    }

    public function findByRegisterNo(
        string $registerNo
    ): ?CashRegister {

        return CashRegister::where(
            'register_no',
            $registerNo
        )->first();
    }

    public function getOpenRegisterByUser(
        int $userId
    ): ?CashRegister {

        return CashRegister::where(
                'user_id',
                $userId
            )
            ->where(
                'status',
                CashRegister::STATUS_OPEN
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
    ): CashRegister {

        return CashRegister::create($data);
    }

    public function update(
        int $id,
        array $data
    ): CashRegister {

        $register = $this->findOrFail($id);

        $register->update($data);

        return $register->fresh();
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

        return CashRegister::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(
        int $id
    ): bool {

        return CashRegister::onlyTrashed()
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
    ): CashRegister {

        $register = $this->findOrFail($id);

        $register->update([
            'status' => $status,
        ]);

        return $register->fresh();
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

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    protected function filter(
        array $filters = []
    ) {

        return CashRegister::query()

            ->with([
                'user',
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
                $filters['user_id'] ?? null,
                fn($q, $userId) => $q->where(
                    'user_id',
                    $userId
                )
            )

            ->when(
                $filters['register_no'] ?? null,
                fn($q, $registerNo) => $q->where(
                    'register_no',
                    'ILIKE',
                    "%{$registerNo}%"
                )
            )

            ->when(
                $filters['name'] ?? null,
                fn($q, $name) => $q->where(
                    'name',
                    'ILIKE',
                    "%{$name}%"
                )
            )

            ->latest();
    }
}
