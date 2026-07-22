<?php

namespace App\Repositories\Sale;

use App\Models\Sale\SaleReturn;
use App\Repositories\Contracts\SaleReturnRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SaleReturnRepository implements SaleReturnRepositoryInterface
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
            ->latest('id')
            ->paginate($perPage);
    }

    public function trashedPaginate(
        int $perPage = 15
    ): LengthAwarePaginator {

        return SaleReturn::onlyTrashed()
            ->latest('id')
            ->paginate($perPage);
    }

    public function all(
        array $filters = []
    ): Collection {

        return $this->filter($filters)
            ->latest('id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id
    ): ?SaleReturn {

        return SaleReturn::with([
            'saleOrder',
            'customer',
            'items.product',
        ])->find($id);
    }

    public function findOrFail(
        int $id
    ): SaleReturn {

        return SaleReturn::with([
            'saleOrder',
            'customer',
            'items.product',
        ])->findOrFail($id);
    }

    public function findByReturnNo(
        string $returnNo
    ): ?SaleReturn {

        return SaleReturn::where(
            'return_no',
            $returnNo
        )->first();
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): SaleReturn {

        return SaleReturn::create($data);
    }

    public function update(
        int $id,
        array $data
    ): SaleReturn {

        $saleReturn = $this->findOrFail($id);

        $saleReturn->update($data);

        return $saleReturn->fresh();
    }

    public function delete(
        int $id
    ): bool {

        return $this->findOrFail($id)->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Trash
    |--------------------------------------------------------------------------
    */

    public function restore(
        int $id
    ): bool {

        return SaleReturn::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(
        int $id
    ): bool {

        return SaleReturn::onlyTrashed()
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
    ): SaleReturn {

        $saleReturn = $this->findOrFail($id);

        $saleReturn->update([
            'status' => $status,
        ]);

        return $saleReturn->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function count(
        array $filters = []
    ): int {

        return $this->filter($filters)->count();
    }

    public function totalAmount(
        array $filters = []
    ): float {

        return (float) $this->filter($filters)
            ->sum('total_amount');
    }

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    private function filter(
        array $filters = []
    ): Builder {

        return SaleReturn::query()
            ->with([
                'saleOrder',
                'customer',
                'items.product',
            ])
            ->when(
                $filters['search'] ?? null,
                function (
                    Builder $query,
                    string $search
                ) {

                    $query->where(function (
                        Builder $query
                    ) use (
                        $search
                    ) {

                        $query
                            ->where(
                                'return_no',
                                'ILIKE',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'remarks',
                                'ILIKE',
                                "%{$search}%"
                            );
                    });
                }
            )
            ->when(
                $filters['status'] ?? null,
                fn(
                    Builder $query,
                    string $status
                ) => $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                $filters['customer_id'] ?? null,
                fn(
                    Builder $query,
                    int $customerId
                ) => $query->where(
                    'customer_id',
                    $customerId
                )
            )
            ->when(
                $filters['sale_order_id'] ?? null,
                fn(
                    Builder $query,
                    int $saleOrderId
                ) => $query->where(
                    'sale_order_id',
                    $saleOrderId
                )
            )
            ->when(
                $filters['from_date'] ?? null,
                fn(
                    Builder $query,
                    string $date
                ) => $query->whereDate(
                    'return_date',
                    '>=',
                    $date
                )
            )
            ->when(
                $filters['to_date'] ?? null,
                fn(
                    Builder $query,
                    string $date
                ) => $query->whereDate(
                    'return_date',
                    '<=',
                    $date
                )
            );
    }
}
