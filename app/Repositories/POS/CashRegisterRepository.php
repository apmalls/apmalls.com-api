<?php

declare(strict_types=1);

namespace App\Repositories\POS;

use App\Models\POS\CashRegister;
use App\Repositories\Contracts\CashRegisterRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CashRegisterRepository implements CashRegisterRepositoryInterface
{
    /**
     * Get paginated registers.
     */
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        return CashRegister::query()

            ->with([
                'user',
                'creator',
                'updater',
            ])

            ->when(

                ! empty($filters['search']),

                function ($query) use ($filters) {

                    $query->where(function ($query) use ($filters) {

                        $query->where(
                            'register_no',
                            'ILIKE',
                            '%' . trim($filters['search']) . '%'
                        )

                        ->orWhere(
                            'name',
                            'ILIKE',
                            '%' . trim($filters['search']) . '%'
                        );

                    });

                }

            )

            ->when(

                ! empty($filters['status']),

                fn ($query) => $query->where(
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
     * Get trashed registers.
     */
    public function trash(
        array $filters = []
    ): LengthAwarePaginator {

        return CashRegister::onlyTrashed()

            ->latest('deleted_at')

            ->paginate(
                $filters['per_page'] ?? 10
            );

    }

    /**
     * Get all registers.
     */
    public function all(): Collection
    {
        return CashRegister::all();
    }

    /**
     * Find register.
     */
    public function find(
        int $id
    ): CashRegister {

        return CashRegister::with([
            'user',
            'creator',
            'updater',
        ])
        ->findOrFail($id);

    }

    /**
     * Find deleted register.
     */
    public function findWithTrashed(
        int $id
    ): CashRegister {

        return CashRegister::onlyTrashed()

            ->findOrFail($id);

    }

    /**
     * Find user's open register.
     */
    public function findOpenRegisterByUser(
        int $userId
    ): ?CashRegister {

        return CashRegister::where(
                'user_id',
                $userId
            )
            ->where(
                'status',
                'Open'
            )
            ->first();

    }

    /**
     * Create register.
     */
    public function create(
        array $data
    ): CashRegister {

        return CashRegister::create($data);

    }

    /**
     * Update register.
     */
    public function update(
        CashRegister $cashRegister,
        array $data
    ): CashRegister {

        $cashRegister->update($data);

        return $cashRegister->refresh();

    }

    /**
     * Delete register.
     */
    public function delete(
        CashRegister $cashRegister
    ): bool {

        return (bool) $cashRegister->delete();

    }

    /**
     * Restore register.
     */
    public function restore(
        int $id
    ): bool {

        return (bool) CashRegister::onlyTrashed()

            ->findOrFail($id)

            ->restore();

    }

    /**
     * Force delete register.
     */
    public function forceDelete(
        int $id
    ): bool {

        return (bool) CashRegister::onlyTrashed()

            ->findOrFail($id)

            ->forceDelete();

    }
}
