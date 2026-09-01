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

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($query) use ($search) {
                $query->where('remarks', 'ILIKE', "%{$search}%")
                    ->orWhereHas('product', fn ($product) => $product
                        ->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('sku', 'ILIKE', "%{$search}%"));
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query
            ->latest()
            ->paginate(min((int) ($filters['per_page'] ?? $filters['paginate'] ?? 15), 100));
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
