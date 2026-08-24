<?php

namespace App\Repositories\Contracts;

use App\Models\Inventory\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockMovementRepositoryInterface
{
    /**
     * Get all stock movements.
     */
    public function getAll(array $filters = []): LengthAwarePaginator;

    /**
     * Find movement by ID.
     */
    public function findById(int $id): ?StockMovement;

    /**
     * Get movements by product.
     */
    public function getByProduct(int $productId): LengthAwarePaginator;

    /**
     * Create movement.
     */
    public function create(array $data): StockMovement;
}
