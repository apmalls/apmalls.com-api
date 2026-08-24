<?php

namespace App\Repositories\Purchase;

use App\Models\Purchase\PurchaseReturn;
use App\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PurchaseReturnRepository implements PurchaseReturnRepositoryInterface
{
    /**
     * Paginated listing.
     */
    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {

        return PurchaseReturn::query()
            ->with([
                'purchaseOrder',
                'supplier',
                'items.product',
            ])
            ->when(
                !empty($filters),
                fn($query) => $this->filter($query, $filters)
            )
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Trashed listing.
     */
    public function trashedPaginate(
        int $perPage = 15
    ): LengthAwarePaginator {

        return PurchaseReturn::onlyTrashed()
            ->with([
                'purchaseOrder',
                'supplier',
                'items.product',
            ])
            ->latest('deleted_at')
            ->paginate($perPage);
    }

    /**
     * Get all purchase returns.
     */
    public function all(
        array $filters = []
    ): Collection {

        return PurchaseReturn::query()
            ->with([
                'purchaseOrder',
                'supplier',
                'items.product',
            ])
            ->when(
                !empty($filters),
                fn($query) => $this->filter($query, $filters)
            )
            ->latest()
            ->get();
    }

    /**
     * Find purchase return.
     */
    public function find(
        int $id
    ): ?PurchaseReturn {

        return PurchaseReturn::with([
            'purchaseOrder',
            'supplier',
            'items.product',
        ])->find($id);
    }

    /**
     * Find or fail.
     */
    public function findOrFail(
        int $id
    ): PurchaseReturn {

        return PurchaseReturn::with([
            'purchaseOrder',
            'supplier',
            'items.product',
        ])->findOrFail($id);
    }

    /**
     * Find by return number.
     */
    public function findByReturnNo(
        string $returnNo
    ): ?PurchaseReturn {

        return PurchaseReturn::where(
            'return_no',
            $returnNo
        )->first();
    }

    /**
     * Create purchase return.
     */
    public function create(
        array $data
    ): PurchaseReturn {

        return PurchaseReturn::create($data);
    }

    /**
     * Update purchase return.
     */
    public function update(
        int $id,
        array $data
    ): PurchaseReturn {

        $purchaseReturn = $this->findOrFail($id);

        $purchaseReturn->update($data);

        return $purchaseReturn->fresh([
            'purchaseOrder',
            'supplier',
            'items.product',
        ]);
    }

    /**
     * Soft delete.
     */
    public function delete(
        int $id
    ): bool {

        return $this->findOrFail($id)->delete();
    }

    /**
     * Restore.
     */
    public function restore(
        int $id
    ): bool {

        return PurchaseReturn::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    /**
     * Force delete.
     */
    public function forceDelete(
        int $id
    ): bool {

        return PurchaseReturn::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();
    }

    /**
     * Change status.
     */
    public function changeStatus(
        int $id,
        string $status
    ): PurchaseReturn {

        $purchaseReturn = $this->findOrFail($id);

        $purchaseReturn->update([
            'status' => $status,
        ]);

        return $purchaseReturn->fresh([
            'purchaseOrder',
            'supplier',
            'items.product',
        ]);
    }

    /**
     * Count purchase returns.
     */
    public function count(
        array $filters = []
    ): int {

        return PurchaseReturn::query()
            ->when(
                !empty($filters),
                fn($query) => $this->filter($query, $filters)
            )
            ->count();
    }

    /**
     * Total returned amount.
     */
    public function totalAmount(
        array $filters = []
    ): float|int {

        return PurchaseReturn::query()
            ->when(
                !empty($filters),
                fn($query) => $this->filter($query, $filters)
            )
            ->sum('total_amount');
    }

    /**
     * Apply filters.
     */
    private function filter(
        $query,
        array $filters
    ): void {

        $query

            ->when(
                $filters['search'] ?? null,
                function ($query, $search) {

                    $query->where(function ($q) use ($search) {

                        $q->where('return_no', 'ILIKE', "%{$search}%")
                            ->orWhere('status', 'ILIKE', "%{$search}%")
                            ->orWhereHas(
                                'supplier',
                                fn($supplier) => $supplier->where(
                                    'name',
                                    'ILIKE',
                                    "%{$search}%"
                                )
                            );
                    });
                }
            )

            ->when(
                $filters['status'] ?? null,
                fn($query, $status) => $query->where(
                    'status',
                    $status
                )
            )

            ->when(
                $filters['supplier_id'] ?? null,
                fn($query, $supplierId) => $query->where(
                    'supplier_id',
                    $supplierId
                )
            )

            ->when(
                $filters['purchase_order_id'] ?? null,
                fn($query, $purchaseOrderId) => $query->where(
                    'purchase_order_id',
                    $purchaseOrderId
                )
            )

            ->when(
                $filters['from_date'] ?? null,
                fn($query, $date) => $query->whereDate(
                    'return_date',
                    '>=',
                    $date
                )
            )

            ->when(
                $filters['to_date'] ?? null,
                fn($query, $date) => $query->whereDate(
                    'return_date',
                    '<=',
                    $date
                )
            );
    }
}
