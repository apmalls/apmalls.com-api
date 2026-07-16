<?php

declare(strict_types=1);

namespace App\Repositories\Sale;

use App\Models\Sale\SaleOrder;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;

class SaleOrderRepository implements SaleOrderRepositoryInterface
{
    /**
     * Customer Orders
     */
    public function index(
        int $customerId
    ): Collection {

        return SaleOrder::query()

            ->with([
                'customer',
                'items.product',
            ])

            ->where(
                'customer_id',
                $customerId
            )

            ->latest()

            ->get();

    }

    /**
     * Find Order
     */
    public function find(
        int $id
    ): SaleOrder {

        return SaleOrder::query()

            ->with([
                'items.product',
            ])

            ->findOrFail($id);

    }

    /**
     * Find Customer Order
     */
    public function findByCustomer(
        int $customerId,
        int $id
    ): SaleOrder {

        return SaleOrder::query()

            ->with([
                'customer',
                'items.product',
            ])

            ->where(
                'customer_id',
                $customerId
            )

            ->findOrFail(
                $id
            );

    }

    /**
     * Create Order
     */
    public function create(
        array $data
    ): SaleOrder {

        return SaleOrder::create($data);

    }

    /**
     * Update Order
     */
    public function update(
        int $id,
        array $data
    ): SaleOrder {

        $order = $this->find($id);

        $order->update($data);

        return $order->refresh();

    }

    /**
     * Cancel Order
     */
    public function cancel(
        int $id
    ): SaleOrder {

        $order = $this->find($id);

        $order->update([

            'status' => 'Cancelled',

        ]);

        return $order->refresh();

    }
}
