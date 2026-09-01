<?php

namespace App\Services\Contracts;

use App\Models\Inventory\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockServiceInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Stock;

    public function findByProductId(int $productId): ?Stock;

    public function create(array $data): Stock;

    public function update(int $id, array $data): Stock;

    public function delete(int $id): bool;
}
