<?php

namespace App\Services\Contracts;

use App\Models\Inventory\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockMovementServiceInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?StockMovement;

    public function getByProduct(int $productId): LengthAwarePaginator;

    public function create(array $data): StockMovement;
}
