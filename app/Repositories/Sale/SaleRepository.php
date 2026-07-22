<?php

namespace App\Repositories\Sale;

use App\Models\Sale\SaleOrder;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SaleRepository implements SaleRepositoryInterface
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

        return SaleOrder::onlyTrashed()
            ->with([
                'customer',
                'billingAddress',
                'shippingAddress',
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
    ): ?SaleOrder {

        return SaleOrder::with([
            'customer',
            'billingAddress',
            'shippingAddress',
            'creator',
            'updater',
        ])->find($id);
    }

    public function findOrFail(
        int $id
    ): SaleOrder {

        return SaleOrder::with([
            'customer',
            'billingAddress',
            'shippingAddress',
            'items.product',
            'items.unit',
            'saleReturns',
            'payments',
            'creator',
            'updater',
        ])->findOrFail($id);
    }

    public function findBySaleNo(
        string $saleNo
    ): ?SaleOrder {

        return SaleOrder::where(
            'sale_no',
            $saleNo
        )->first();
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): SaleOrder {

        return SaleOrder::create($data);
    }

    public function update(
        int $id,
        array $data
    ): SaleOrder {

        $sale = $this->findOrFail($id);

        $sale->update($data);

        return $sale->fresh();
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

        return SaleOrder::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(
        int $id
    ): bool {

        return SaleOrder::onlyTrashed()
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
    ): SaleOrder {

        $sale = $this->findOrFail($id);

        $sale->update([
            'status' => $status,
        ]);

        return $sale->fresh();
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

        return SaleOrder::query()

            ->with([
                'customer',
                'billingAddress',
                'shippingAddress',
                'creator',
                'updater',
            ])

            ->when(
                $filters['status'] ?? null,
                fn($q, $status) => $q->where('status', $status)
            )

            ->when(
                $filters['payment_status'] ?? null,
                fn($q, $status) => $q->where('payment_status', $status)
            )

            ->when(
                $filters['customer_id'] ?? null,
                fn($q, $customer) => $q->where('customer_id', $customer)
            )

            ->when(
                $filters['sale_no'] ?? null,
                fn($q, $saleNo) => $q->where(
                    'sale_no',
                    'ILIKE',
                    "%{$saleNo}%"
                )
            )

            ->when(
                $filters['invoice_no'] ?? null,
                fn($q, $invoiceNo) => $q->where(
                    'invoice_no',
                    'ILIKE',
                    "%{$invoiceNo}%"
                )
            )

            ->when(
                $filters['from_date'] ?? null,
                fn($q, $date) => $q->whereDate(
                    'sale_date',
                    '>=',
                    $date
                )
            )

            ->when(
                $filters['to_date'] ?? null,
                fn($q, $date) => $q->whereDate(
                    'sale_date',
                    '<=',
                    $date
                )
            )

            ->latest();
    }
}
