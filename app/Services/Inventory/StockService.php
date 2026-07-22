<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Stock;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\StockRepositoryInterface;
use App\Services\Contracts\StockServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockService implements StockServiceInterface
{
    public function __construct(
        protected StockRepositoryInterface $stockRepository
    ) {}

    public function getAll(array $filters = []): Collection|LengthAwarePaginator
    {
        return $this->stockRepository->getAll($filters);
    }

    public function findById(int $id): ?Stock
    {
        return $this->stockRepository->findById($id);
    }

    public function findByProductId(int $productId): ?Stock
    {
        return $this->stockRepository->findByProductId($productId);
    }

    public function create(array $data): Stock
    {
        return $this->stockRepository->create($data);
    }

    public function update(int $id, array $data): Stock
    {
        return $this->stockRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->stockRepository->delete($id);
    }
}
