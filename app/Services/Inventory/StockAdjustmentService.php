<?php

namespace App\Services\Inventory;

use App\Models\Inventory\StockAdjustment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Services\Contracts\StockAdjustmentServiceInterface;

class StockAdjustmentService implements StockAdjustmentServiceInterface
{
    public function __construct(
        protected StockAdjustmentRepositoryInterface $adjustmentRepository
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return $this->adjustmentRepository->getAll($filters);
    }

    public function findById(int $id): ?StockAdjustment
    {
        return $this->adjustmentRepository->findById($id);
    }

    public function create(array $data): StockAdjustment
    {
        return $this->adjustmentRepository->create($data);
    }

    public function update(int $id, array $data): StockAdjustment
    {
        return $this->adjustmentRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->adjustmentRepository->delete($id);
    }
}
