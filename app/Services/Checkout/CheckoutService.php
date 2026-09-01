<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Helpers\StockHelper;
use App\Models\Cart\Cart;
use App\Models\Sale\SaleOrder;
use App\Services\Contracts\CartServiceInterface;
use App\Services\Contracts\CheckoutServiceInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Repositories\Contracts\SaleOrderItemRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CheckoutService implements CheckoutServiceInterface
{
    /**
     * Create service instance.
     */
    public function __construct(

        protected CartRepositoryInterface $cartRepository,

        protected CartServiceInterface $cartService,

        protected ProductRepositoryInterface $productRepository,

        protected SaleRepositoryInterface $saleRepository,

        protected SaleOrderItemRepositoryInterface $saleOrderItemRepository,

    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Generate sale number.
     */
    protected function generateSaleNo(): string
    {
        do {

            $saleNo = 'SAL-'
                . now(config('app.business_timezone'))->format('Ymd')
                . '-'
                . strtoupper(
                    Str::random(6)
                );

        } while (

            $this->saleRepository
                ->findBySaleNo($saleNo)

        );

        return $saleNo;
    }

    /**
     * Get active cart.
     */
    protected function getCart(
        int $customerId
    ): Cart {

        $cart = $this->cartService->getActiveCart(
            $customerId
        );

        if (!$cart) {

            throw new \Exception(
                'Cart not found.'
            );

        }

        return $cart;
    }

    /**
     * Validate cart.
     */
    protected function validateCart(
        Cart $cart
    ): void {

        if (

            $cart->items->isEmpty()

        ) {

            throw new \Exception(
                'Cart is empty.'
            );

        }

    }

    /**
     * Validate stock.
     */
    protected function validateStock(
        Cart $cart
    ): void {

        foreach (

            $cart->items as $item

        ) {

            $product = $this->productRepository
                ->find(
                    $item->product_id
                );

            if (!$product) {

                throw new \Exception(
                    'Product not found.'
                );

            }

            if (

                !$product->is_active

            ) {

                throw new \Exception(

                    "{$product->name} is inactive."

                );

            }

            if (StockHelper::availableStock($product->id) < $item->quantity) {

                throw new \Exception(

                    "{$product->name} is out of stock."

                );

            }

        }

    }

    /**
     * Create draft sale order.
     */
    protected function createSaleOrder(
        Cart $cart,
        array $data
    ): SaleOrder {

        return $this->saleRepository
            ->create([

                'customer_id' => $cart->customer_id,

                'sale_no' => $this->generateSaleNo(),

                'sale_date' => now(config('app.business_timezone'))->toDateString(),

                'sub_total' => $cart->subtotal,

                'discount_amount' => $cart->discount_amount,

                'tax_amount' => $cart->tax_amount,

                'shipping_amount' => $cart->shipping_charge,

                'other_amount' => 0,

                'round_off' => 0,

                'grand_total' => $cart->grand_total,

                'paid_amount' => 0,

                'due_amount' => $cart->grand_total,

                'refund_amount' => 0,

                'payment_status' => SaleOrder::PAYMENT_PENDING,

                'status' => SaleOrder::STATUS_DRAFT,

                'billing_address_id' => $data['billing_address_id'],

                'shipping_address_id' => $data['shipping_address_id'],

                'remarks' => $data['remarks'] ?? null,

            ]);

    }

    /**
     * Create sale order items.
     */
    protected function createSaleOrderItems(
        SaleOrder $saleOrder,
        Cart $cart
    ): void {

        $items = [];

        foreach ($cart->items as $cartItem) {

            $product = $this->productRepository->find(
                $cartItem->product_id
            );

            $items[] = [

                'sale_order_id' => $saleOrder->id,

                'product_id' => $product->id,

                'unit_id' => $product->unit_id,

                'quantity' => $cartItem->quantity,

                'returned_quantity' => 0,

                'purchase_price' => $product->purchase_price,

                'selling_price' => $cartItem->price,

                'tax_percent' => $cartItem->tax_percent,

                'tax_amount' => $cartItem->tax_amount,

                'discount_percent' => $cartItem->discount_percent,

                'discount_amount' => $cartItem->discount_amount,

                'line_total' => $cartItem->subtotal,

                'created_at' => now(),

                'updated_at' => now(),

            ];

        }

        $this->saleOrderItemRepository
            ->createMany($items);

    }

    /**
     * Deduct product stock.
     */
    protected function deductProductStock(
        SaleOrder $saleOrder
    ): void {

        foreach ($saleOrder->items as $item) {

            StockHelper::decrease(
                productId: $item->product_id,
                quantity: $item->quantity,
                referenceType: SaleOrder::class,
                referenceId: $saleOrder->id,
                remarks: 'Website checkout',
                idempotencyKey: "sale:{$saleOrder->id}:product:{$item->product_id}:checkout"
            );

        }

    }

    /**
     * Clear customer cart.
     */
    protected function clearCustomerCart(
        Cart $cart
    ): void {

        $this->cartRepository->delete(
            $cart->id
        );

    }



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
    ): LengthAwarePaginator {

        return $this
            ->saleRepository
            ->customerOrders(
                $customerId,
                $perPage
            );

    }

    /**
     * Customer order details.
     */
    public function orderDetails(
        int $customerId,
        string $saleNo
    ): ?SaleOrder {

        return $this
            ->saleRepository
            ->customerOrder(
                $customerId,
                $saleNo
            );

    }

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
    ): SaleOrder {

        $saleOrder = $this
            ->saleRepository
            ->findBySaleNo(
                $saleNo
            );

        if (!$saleOrder) {

            throw new \Exception(
                'Order not found.'
            );

        }

        if ($saleOrder->customer_id !== $customerId) {

            throw new \Exception(
                'Unauthorized.'
            );

        }

        if ($saleOrder->isCancelled()) {

            throw new \Exception(
                'Order is already cancelled.'
            );

        }

        if ($saleOrder->isCompleted()) {

            throw new \Exception(
                'Completed order cannot be cancelled.'
            );

        }

        if ($saleOrder->isConfirmed()) {

            throw new \Exception(
                'Confirmed order cannot be cancelled. Please refund first.'
            );

        }



        return $this
            ->saleRepository
            ->changeStatus(
                $saleOrder->id,
                SaleOrder::STATUS_CANCELLED
            );

    }

    /*
    |--------------------------------------------------------------------------
    | Checkout
    |--------------------------------------------------------------------------
    */

    /**
     * Create sale order from cart.
     */
    public function checkout(
        int $customerId,
        array $data
    ): SaleOrder {

        return DB::transaction(function () use ($customerId, $data) {

            $cart = $this->getCart(
                $customerId
            );

            $this->validateCart(
                $cart
            );

            $this->validateStock(
                $cart
            );

            $saleOrder = $this->getDraftOrder(
                $cart,
                $data
            );

            $this->createSaleOrderItems(

                $saleOrder,

                $cart

            );



            /*
            |--------------------------------------------------------------------------
            | Coupon
            |--------------------------------------------------------------------------
            */

            // Future:
            // Coupon usage increment.

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            // Future:
            // Razorpay order create.



            return $this->saleRepository
                ->findOrFail(
                    $saleOrder->id
                );

        });

    }

    /**
     * Payment success callback.
     */
    public function paymentSuccess(
        int $saleOrderId,
        array $paymentData = []
    ): SaleOrder {

        return $this->confirmOrder($saleOrderId);

    }

    public function confirmCashOnDelivery(int $saleOrderId): SaleOrder
    {
        return $this->confirmOrder($saleOrderId);
    }

    private function confirmOrder(int $saleOrderId): SaleOrder
    {

        return DB::transaction(function () use ($saleOrderId) {

            $saleOrder = SaleOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($saleOrderId);

            /*
            |--------------------------------------------------------------------------
            | Prevent duplicate processing
            |--------------------------------------------------------------------------
            */

            if (

                in_array(
                    $saleOrder->status,
                    [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_COMPLETED],
                    true
                )

            ) {

                return $saleOrder;

            }

            if ($saleOrder->status !== SaleOrder::STATUS_DRAFT) {
                throw new \Exception('Only a draft order can be confirmed.');
            }

            /*
|--------------------------------------------------------------------------
| Deduct Product Stock
|--------------------------------------------------------------------------
*/

            $this->deductProductStock(
                $saleOrder
            );

            /*
            |--------------------------------------------------------------------------
            | Update Sale Order
            |--------------------------------------------------------------------------
            */

            $saleOrder = $this->saleRepository->update(

                $saleOrder->id,

                [

                    'status' => SaleOrder::STATUS_CONFIRMED,

                    'invoice_date' => now(),

                ]

            );

            /*
            |--------------------------------------------------------------------------
            | Clear Cart
            |--------------------------------------------------------------------------
            */

            $cart = $this->cartRepository
                ->getActiveCart(
                    $saleOrder->customer_id
                );

            if ($cart) {

                $this->cartRepository->delete(
                    $cart->id
                );

            }

            return $saleOrder;

        });

    }

    /**
     * Payment failed callback.
     */
    public function paymentFailed(
        int $saleOrderId,
        array $paymentData = []
    ): SaleOrder {

        return DB::transaction(function () use ($saleOrderId, $paymentData) {

            $saleOrder = $this->saleRepository
                ->findOrFail(
                    $saleOrderId
                );

            /*
            |--------------------------------------------------------------------------
            | Restore Product Stock
            |--------------------------------------------------------------------------
            */



            /*
            |--------------------------------------------------------------------------
            | Payment Failure Log
            |--------------------------------------------------------------------------
            */

            // TODO:
            // Save payment failure response if required.
            // Example:
            // - gateway response
            // - error code
            // - error message
            // - transaction id (if available)

            /*
            |--------------------------------------------------------------------------
            | Cart
            |--------------------------------------------------------------------------
            */

            // Keep cart unchanged so customer can retry payment.

            /*
            |--------------------------------------------------------------------------
            | Sale Order
            |--------------------------------------------------------------------------
            */

            // Keep order as Draft.
            // Do not change payment status here.
            // PaymentService already manages payment status.

            return $saleOrder;

        });

    }


    /**
     * Get or create draft order.
     */
    protected function getDraftOrder(
        Cart $cart,
        array $data
    ): SaleOrder {

        $draft = $this->saleRepository
            ->findDraftByCustomer(
                $cart->customer_id
            );

        if (!$draft) {

            return $this->createSaleOrder(
                $cart,
                $data
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Remove old items
        |--------------------------------------------------------------------------
        */

        $this->saleOrderItemRepository
            ->deleteBySaleOrder(
                $draft->id
            );

        /*
        |--------------------------------------------------------------------------
        | Update totals
        |--------------------------------------------------------------------------
        */

        return $this->saleRepository
            ->update(
                $draft->id,
                [

                    'sub_total' => $cart->subtotal,

                    'discount_amount' => $cart->discount_amount,

                    'tax_amount' => $cart->tax_amount,

                    'shipping_amount' => $cart->shipping_charge,

                    'grand_total' => $cart->grand_total,

                    'billing_address_id' => $data['billing_address_id'],

                    'shipping_address_id' => $data['shipping_address_id'],

                    'remarks' => $data['remarks'] ?? null,

                ]
            );

    }

}
