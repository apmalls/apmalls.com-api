<?php

namespace App\Repositories\Inventory;

use App\Models\Inventory\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMovementRepository implements StockMovementRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = StockMovement::query()
            ->with(['product', 'creator']);

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (!empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        return $query
            ->latest()
            ->paginate($filters['paginate'] ?? 15);
    }

    public function findById(int $id): ?StockMovement
    {
        return StockMovement::with([
            'product',
            'creator',
        ])->find($id);
    }

    public function getByProduct(int $productId): LengthAwarePaginator
    {
        return StockMovement::with([
                'creator',
            ])
            ->where('product_id', $productId)
            ->latest()
            ->paginate(15);
    }

    public function create(array $data): StockMovement
    {
        return StockMovement::create($data);
    }
}
