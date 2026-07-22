<?php

namespace App\Repositories\Contracts;

use App\Models\Inventory\StockAdjustment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockAdjustmentRepositoryInterface
{
    /**
     * Get all stock adjustments.
     */
    public function getAll(array $filters = []): LengthAwarePaginator;

    /**
     * Find stock adjustment by ID.
     */
    public function findById(int $id): ?StockAdjustment;

    /**
     * Create stock adjustment.
     */
    public function create(array $data): StockAdjustment;

    /**
     * Update stock adjustment.
     */
    public function update(int $id, array $data): StockAdjustment;

    /**
     * Delete stock adjustment.
     */
    public function delete(int $id): bool;
}
