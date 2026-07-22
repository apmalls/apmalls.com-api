<?php

namespace App\Repositories\Contracts;

use App\Models\Inventory\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StockRepositoryInterface
{
    /**
     * Get all stocks
     */
    public function getAll(array $filters = []): Collection|LengthAwarePaginator;

    /**
     * Find stock by id
     */
    public function findById(int $id): ?Stock;

    /**
     * Find stock by product id
     */
    public function findByProductId(int $productId): ?Stock;

    /**
     * Create stock
     */
    public function create(array $data): Stock;

    /**
     * Update stock
     */
    public function update(int $id, array $data): Stock;

    /**
     * Delete stock
     */
    public function delete(int $id): bool;
}
