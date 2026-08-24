<?php

namespace App\Repositories\Inventory;

use App\Models\Inventory\Stock;
use App\Repositories\Contracts\StockRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StockRepository implements StockRepositoryInterface
{
    public function getAll(array $filters = []): Collection|LengthAwarePaginator
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

        if (!empty($filters['paginate'])) {
            return $query->latest()->paginate($filters['paginate']);
        }

        return $query->latest()->get();
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
