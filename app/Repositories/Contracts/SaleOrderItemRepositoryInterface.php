<?php

namespace App\Repositories\Contracts;

use App\Models\Sale\SaleOrderItem;
use Illuminate\Database\Eloquent\Collection;

interface SaleOrderItemRepositoryInterface
{
    public function create(array $data): SaleOrderItem;

    public function createMany(array $items): bool;

    public function update(int $id, array $data): SaleOrderItem;

    public function delete(int $id): bool;

    public function deleteBySaleOrder(int $saleOrderId): bool;

    public function find(int $id): ?SaleOrderItem;

    public function findOrFail(int $id): SaleOrderItem;

    public function getBySaleOrder(int $saleOrderId): Collection;
}
