<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Sale\SaleResource;
use App\Http\Resources\Payment\PaymentResource;

class POSCheckoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'sale' => new SaleResource(
                $this['sale']
            ),

            'payment' => new PaymentResource(
                $this['payment']
            ),

            'message' => $this['message'],

        ];
    }
}
