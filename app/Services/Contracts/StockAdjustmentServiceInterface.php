<?php

namespace App\Services\Contracts;

use App\Models\Inventory\StockAdjustment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockAdjustmentServiceInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?StockAdjustment;

    public function create(array $data): StockAdjustment;

    public function update(int $id, array $data): StockAdjustment;

    public function delete(int $id): bool;
}
