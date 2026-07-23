<?php

namespace App\Http\Resources\POS;

use App\Http\Resources\POS\ProductResource;
use App\Http\Resources\Payment\PaymentModeResource;
use App\Http\Resources\Sale\SaleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class POSDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'session' => new CashRegisterSessionResource(
                $this['session']
            ),

            'today_sale' => $this['today_sale'],

            'today_order' => $this['today_order'],

            'hold_count' => $this['hold_count'],

            'customer_count' => $this['customer_count'],

            'recent_sales' => SaleResource::collection(
                $this['recent_sales']
            ),

            'payment_modes' => PaymentModeResource::collection(
                $this['payment_modes']
            ),

            'quick_products' => ProductResource::collection(
                $this['quick_products']
            ),
        ];
    }
}
