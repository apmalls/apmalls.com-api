<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Sale\SaleOrder;
use Illuminate\Database\Eloquent\Collection;

interface SaleOrderRepositoryInterface
{
    /**
     * Customer Orders
     */
    public function index(
        int $customerId
    ): Collection;

    /**
     * Find Order
     */
    public function find(
        int $id
    ): SaleOrder;


    public function show(
        int $customerId,
        int $id
    ): SaleOrder;
    /**
     * Find Customer Order
     */
    public function findByCustomer(
        int $customerId,
        int $id
    ): SaleOrder;

    /**
     * Create Order
     */
    public function create(
        array $data
    ): SaleOrder;

    /**
     * Update Order
     */
    public function update(
        int $id,
        array $data
    ): SaleOrder;

    /**
     * Cancel Order
     */
    public function cancel(
        int $id
    ): SaleOrder;

    public function generateInvoiceNumber(): string;

    public function updateInvoice(
        int $id,
        array $data
    ): SaleOrder;
}
