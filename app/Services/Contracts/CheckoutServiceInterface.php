<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Sale\SaleOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CheckoutServiceInterface
{
    /*
    |--------------------------------------------------------------------------
    | Checkout
    |--------------------------------------------------------------------------
    */

    /**
     * Create draft sale order from customer cart.
     */
    public function checkout(
        int $customerId,
        array $data
    ): SaleOrder;

    /**
     * Confirm an unpaid order for cash collection on delivery.
     */
    public function confirmCashOnDelivery(int $saleOrderId): SaleOrder;

    /*
    |--------------------------------------------------------------------------
    | Payment
    |--------------------------------------------------------------------------
    */

    /**
     * Payment success callback.
     */
    public function paymentSuccess(
        int $saleOrderId,
        array $paymentData = []
    ): SaleOrder;

    /**
     * Payment failed callback.
     */
    public function paymentFailed(
        int $saleOrderId,
        array $paymentData = []
    ): SaleOrder;

    /*
    |--------------------------------------------------------------------------
    | Customer Orders
    |--------------------------------------------------------------------------
    */

    /**
     * Customer order history.
     */
    public function myOrders(
        int $customerId,
        int $perPage = 10
    ): LengthAwarePaginator;

    /**
     * Customer order details.
     */
    public function orderDetails(
        int $customerId,
        string $saleNo
    ): ?SaleOrder;

    /*
    |--------------------------------------------------------------------------
    | Order Actions
    |--------------------------------------------------------------------------
    */

    /**
     * Cancel customer order.
     */
    public function cancelOrder(
        int $customerId,
        string $saleNo
    ): SaleOrder;
}
