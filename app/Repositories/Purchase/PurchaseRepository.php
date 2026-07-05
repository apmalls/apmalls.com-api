<?php

declare(strict_types=1);

namespace App\Repositories\Purchase;

use App\Models\Purchase\PurchaseOrder;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PurchaseRepository implements PurchaseRepositoryInterface
{
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        return PurchaseOrder::query()

            ->with([
                'supplier',
                'items.product',
            ])

            ->latest()

            ->paginate(
                $filters['per_page'] ?? 10
            );
    }

    public function all(): Collection
    {
        return PurchaseOrder::all();
    }

    public function find(
        int $id
    ): PurchaseOrder {

        return PurchaseOrder::with([
            'supplier',
            'items.product',
        ])
            ->findOrFail($id);

    }

    public function create(
        array $data
    ): PurchaseOrder {

        return PurchaseOrder::create($data);

    }

    public function update(
        PurchaseOrder $purchase,
        array $data
    ): PurchaseOrder {

        $purchase->update($data);

        return $purchase->refresh();

    }

    public function delete(
        PurchaseOrder $purchase
    ): bool {

        return (bool) $purchase->delete();

    }

    public function restore(
        int $id
    ): bool {

        return (bool) PurchaseOrder::onlyTrashed()

            ->findOrFail($id)

            ->restore();

    }

    public function forceDelete(
        int $id
    ): bool {

        return (bool) PurchaseOrder::onlyTrashed()

            ->findOrFail($id)

            ->forceDelete();

    }
}
