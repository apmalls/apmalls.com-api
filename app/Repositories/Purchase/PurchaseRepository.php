<?php

namespace App\Repositories\Purchase;

use App\Models\Purchase\PurchaseOrder;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PurchaseRepository implements PurchaseRepositoryInterface
{
    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {

        $query = PurchaseOrder::query()
            ->with([
                'supplier',
                'warehouse',
                'creator',
                'updater'
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['purchase_no'])) {
            $query->where('purchase_no', 'ILIKE', '%' . $filters['purchase_no'] . '%');
        }

        if (!empty($filters['invoice_no'])) {
            $query->where('invoice_no', 'ILIKE', '%' . $filters['invoice_no'] . '%');
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('purchase_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('purchase_date', '<=', $filters['to_date']);
        }

        return $query
            ->latest('id')
            ->paginate($perPage);
    }

    public function all(array $filters = []): Collection
    {
        return $this->paginate(PHP_INT_MAX, $filters)->getCollection();
    }

    public function trashedPaginate(int $perPage = 15): LengthAwarePaginator
    {
        return PurchaseOrder::onlyTrashed()
            ->with([
                'supplier',
                'warehouse'
            ])
            ->latest('id')
            ->paginate($perPage);
    }

    public function find(int $id): ?PurchaseOrder
    {
        return PurchaseOrder::with([
            'supplier',
            'warehouse',
            'items.product',
            'items.unit',
            'payments',
            'purchaseReturns'
        ])->find($id);
    }

    public function findOrFail(int $id): PurchaseOrder
    {
        return PurchaseOrder::with([
            'supplier',
            'warehouse',
            'items.product',
            'items.unit',
            'payments',
            'purchaseReturns'
        ])->findOrFail($id);
    }

    public function findByPurchaseNo(string $purchaseNo): ?PurchaseOrder
    {
        return PurchaseOrder::where(
            'purchase_no',
            $purchaseNo
        )->first();
    }

    public function create(array $data): PurchaseOrder
    {
        return PurchaseOrder::create($data);
    }

    public function update(
        int $id,
        array $data
    ): PurchaseOrder {

        $purchase = $this->findOrFail($id);

        $purchase->update($data);

        return $purchase->fresh();
    }

    public function delete(int $id): bool
    {
        return $this->findOrFail($id)->delete();
    }

    public function restore(int $id): bool
    {
        return PurchaseOrder::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(int $id): bool
    {
        return PurchaseOrder::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();
    }

    public function changeStatus(
        int $id,
        string $status
    ): PurchaseOrder {

        $purchase = $this->findOrFail($id);

        $purchase->update([
            'status' => $status,
        ]);

        return $purchase->fresh();
    }

    public function count(array $filters = []): int
    {
        return PurchaseOrder::count();
    }

    public function totalAmount(array $filters = []): float
    {
        return (float) PurchaseOrder::sum('grand_total');
    }

    public function filter(array $filters = []): Collection
    {
        return $this->paginate(
            PHP_INT_MAX,
            $filters
        )->getCollection();
    }
}
