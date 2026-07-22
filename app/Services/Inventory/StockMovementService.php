<?php

namespace App\Services\Inventory;

use App\Models\Inventory\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Services\Contracts\StockMovementServiceInterface;

class StockMovementService implements StockMovementServiceInterface
{
    public function __construct(
        protected StockMovementRepositoryInterface $movementRepository
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return $this->movementRepository->getAll($filters);
    }

    public function findById(int $id): ?StockMovement
    {
        return $this->movementRepository->findById($id);
    }

    public function getByProduct(int $productId): LengthAwarePaginator
    {
        return $this->movementRepository->getByProduct($productId);
    }

    public function create(array $data): StockMovement
    {
        return $this->movementRepository->create($data);
    }
}
