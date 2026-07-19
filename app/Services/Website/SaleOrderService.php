<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Repositories\Contracts\CartItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\SaleOrder;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CustomerAddressRepositoryInterface;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;
use App\Repositories\Contracts\SaleOrderItemRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Http\Response;

class SaleOrderService
{
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        protected CartItemRepositoryInterface $cartItemRepository,
        protected CustomerAddressRepositoryInterface $addressRepository,
        protected SaleOrderRepositoryInterface $saleOrderRepository,
        protected SaleOrderItemRepositoryInterface $saleOrderItemRepository,
    ) {
    }

    /**
     * Customer Orders
     */
    public function index(
        int $customerId
    ): Collection {

        return $this->saleOrderRepository
            ->index(
                $customerId
            );

    }

    /**
     * Order Details
     */
    public function show(
        int $customerId,
        int $orderId
    ): SaleOrder {

        return $this->saleOrderRepository
            ->findByCustomer(

                $customerId,

                $orderId

            );

    }

    /**
     * Place Order
     */
    public function placeOrder(
        int $customerId,
        array $data
    ): SaleOrder {

        return DB::transaction(function () use ($customerId, $data) {

            /*
            |--------------------------------------------------------------------------
            | Customer Cart
            |--------------------------------------------------------------------------
            */

            $cart = $this->cartRepository
                ->active($customerId);

            if (!$cart) {

                abort(
                    422,
                    'Cart not found.'
                );

            }

            if ($cart->items->isEmpty()) {

                abort(
                    422,
                    'Cart is empty.'
                );

            }

            /*
            |--------------------------------------------------------------------------
            | Billing Address
            |--------------------------------------------------------------------------
            */

            $billingAddress = $this->addressRepository
                ->findByCustomer(

                    $customerId,

                    $data['billing_address_id']

                );

            /*
            |--------------------------------------------------------------------------
            | Shipping Address
            |--------------------------------------------------------------------------
            */

            $shippingAddress = $this->addressRepository
                ->findByCustomer(

                    $customerId,

                    $data['shipping_address_id']

                );

            /*
            |--------------------------------------------------------------------------
            | Create Order
            |--------------------------------------------------------------------------
            */

            $order = $this->saleOrderRepository
                ->create([

                    'customer_id' => $customerId,

                    'sale_no' => $this->saleNo(),

                    'invoice_no' => null,

                    'sale_date' => now(),
                    'billing_address_id' => $billingAddress->id,

                    'shipping_address_id' => $shippingAddress->id,

                    'sub_total' => $cart->subtotal,

                    'discount_amount' => $cart->discount_amount,

                    'tax_amount' => $cart->tax_amount,

                    'shipping_charge' => $cart->shipping_charge,

                    'other_charge' => 0,

                    'grand_total' => $cart->grand_total,

                    'paid_amount' => 0,

                    'due_amount' => $cart->grand_total,

                    'status' => 'Draft',

                    'remarks' => $data['remarks'] ?? null,

                    'created_by' => auth()->id(),

                ]);

            /*
            |--------------------------------------------------------------------------
            | Next Part
            |--------------------------------------------------------------------------
            */

            return $this->createOrderItems(

                $order,

                $cart

            );

        });

    }

    /**
     * Generate Sale Number
     */
    private function saleNo(): string
    {
        return 'SO-' . str_pad(

            (string) (

                SaleOrder::max('id') + 1

            ),

            6,

            '0',

            STR_PAD_LEFT

        );
    }

    /**
     * Create Order Items
     */
    private function createOrderItems(
        SaleOrder $order,
        $cart
    ): SaleOrder {

        foreach ($cart->items as $item) {

            $this->saleOrderItemRepository
                ->create([

                    'sale_order_id' => $order->id,

                    'product_id' => $item->product_id,

                    'purchase_price' => $item->product->purchase_price,

                    'selling_price' => $item->price,

                    'quantity' => $item->quantity,

                    'tax_percent' => $item->tax_percent,

                    'tax_amount' => $item->tax_amount,

                    'discount_percent' => $item->discount_percent,

                    'discount_amount' => $item->discount_amount,

                    'line_total' => $item->subtotal,

                ]);

            /*
            |--------------------------------------------------------------------------
            | Inventory Module
            |--------------------------------------------------------------------------
            |
            | Reduce Stock
            | Stock Movement
            |
            | This will be implemented after
            | Purchase & Inventory Module.
            |
            */

        }

        /*
        |--------------------------------------------------------------------------
        | Clear Cart
        |--------------------------------------------------------------------------
        */

        $this->cartItemRepository
            ->clear($cart->id);

        return $this->saleOrderRepository
            ->find($order->id);

    }


    /**
     * Cancel Order
     */
    public function cancel(
        int $customerId,
        int $orderId
    ): SaleOrder {

        $order = $this->saleOrderRepository
            ->findByCustomer(

                $customerId,

                $orderId

            );

        if (
            !in_array($order->status, [

                'Draft',

                'Confirmed',

            ])
        ) {

            abort(
                422,
                'This order cannot be cancelled.'
            );

        }

        return $this->saleOrderRepository
            ->cancel(
                $orderId
            );

    }

    /**
     * Download Invoice
     */
    /**
     * Download Invoice
     */
    public function downloadInvoice(
        int $customerId,
        int $orderId
    ): Response {
        $order = $this->saleOrderRepository
            ->show(
                $customerId,
                $orderId
            );

        if (!$order->invoice_no) {

            $this->saleOrderRepository
                ->updateInvoice(
                    $order->id,
                    [
                        'invoice_no' => $this->saleOrderRepository
                            ->generateInvoiceNumber(),

                        'invoice_date' => now(),
                    ]
                );

            $order = $this->saleOrderRepository
                ->show(
                    $customerId,
                    $orderId
                );
        }

        return Pdf::loadView(
            'invoices.order',
            [
                'order' => $order,
            ]
        )->download(
                $order->invoice_no . '.pdf'
            );
    }
}
