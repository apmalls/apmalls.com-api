<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Sale\SaleOrderItem;

interface SaleOrderItemRepositoryInterface
{
    /**
     * Create Order Item
     */
    public function create(
        array $data
    ): SaleOrderItem;
}
