<?php

declare(strict_types=1);

namespace App\Repositories\Sale;

use App\Models\Sale\SaleOrderItem;
use App\Repositories\Contracts\SaleOrderItemRepositoryInterface;

class SaleOrderItemRepository implements SaleOrderItemRepositoryInterface
{
    /**
     * Create Order Item
     */
    public function create(
        array $data
    ): SaleOrderItem {

        return SaleOrderItem::create($data);

    }
}
