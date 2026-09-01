<?php

namespace App\Repositories\Inventory;

use App\Models\Inventory\Stock;
use App\Repositories\Contracts\StockRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockRepository implements StockRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Stock::query()
            ->with('product');

        if (!empty($filters['search'])) {

            $search = $filters['search'];

            $query->whereHas('product', function ($q) use ($search) {

                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");

            });
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['stock_status'])) {
            match ($filters['stock_status']) {
                'out_of_stock' => $query->where('available_stock', '<=', 0),
                'low_stock' => $query
                    ->where('available_stock', '>', 0)
                    ->whereColumn('available_stock', '<=', 'minimum_stock'),
                'in_stock' => $query->whereColumn('available_stock', '>', 'minimum_stock'),
                'reconciliation_required' => $query->where('reconciliation_required', true),
                default => null,
            };
        }

        return $query
            ->orderBy('available_stock')
            ->orderBy('id')
            ->paginate(min((int) ($filters['per_page'] ?? $filters['paginate'] ?? 15), 100));
    }

    public function findById(int $id): ?Stock
    {
        return Stock::with('product')->find($id);
    }

    public function findByProductId(int $productId): ?Stock
    {
        return Stock::where('product_id', $productId)
            ->first();
    }

    public function create(array $data): Stock
    {
        return Stock::create($data);
    }

    public function update(int $id, array $data): Stock
    {
        $stock = $this->findById($id);

        $stock->update($data);

        return $stock->fresh();
    }

    public function delete(int $id): bool
    {
        $stock = $this->findById($id);

        return $stock->delete();
    }
}
