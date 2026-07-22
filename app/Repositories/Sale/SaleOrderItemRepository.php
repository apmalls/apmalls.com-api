<?php

namespace App\Repositories\Sale;

use App\Models\Sale\SaleOrderItem;
use App\Repositories\Contracts\SaleOrderItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SaleOrderItemRepository implements SaleOrderItemRepositoryInterface
{
    public function create(array $data): SaleOrderItem
    {
        return SaleOrderItem::create($data);
    }

    public function createMany(array $items): bool
    {
        return SaleOrderItem::insert($items);
    }

    public function update(int $id, array $data): SaleOrderItem
    {
        $item = $this->findOrFail($id);

        $item->update($data);

        return $item->fresh();
    }

    public function delete(int $id): bool
    {
        return $this->findOrFail($id)->delete();
    }

    public function deleteBySaleOrder(int $saleOrderId): bool
    {
        return SaleOrderItem::where(
            'sale_order_id',
            $saleOrderId
        )->delete();
    }

    public function find(int $id): ?SaleOrderItem
    {
        return SaleOrderItem::find($id);
    }

    public function findOrFail(int $id): SaleOrderItem
    {
        return SaleOrderItem::findOrFail($id);
    }

    public function getBySaleOrder(int $saleOrderId): Collection
    {
        return SaleOrderItem::where(
            'sale_order_id',
            $saleOrderId
        )
        ->with([
            'product',
            'unit'
        ])
        ->get();
    }
}
