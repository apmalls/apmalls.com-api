<?php

namespace App\Repositories\Inventory;

use App\Models\Inventory\StockAdjustment;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockAdjustmentRepository implements StockAdjustmentRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = StockAdjustment::query()
            ->with([
                'product',
                'creator',
                'updater',
            ]);

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query
            ->latest()
            ->paginate($filters['paginate'] ?? 15);
    }

    public function findById(int $id): ?StockAdjustment
    {
        return StockAdjustment::with([
            'product',
            'creator',
            'updater',
        ])->find($id);
    }

    public function create(array $data): StockAdjustment
    {
        return StockAdjustment::create($data);
    }

    public function update(int $id, array $data): StockAdjustment
    {
        $adjustment = $this->findById($id);

        $adjustment->update($data);

        return $adjustment->fresh();
    }

    public function delete(int $id): bool
    {
        $adjustment = $this->findById($id);

        return $adjustment->delete();
    }
}
