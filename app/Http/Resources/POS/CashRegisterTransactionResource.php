<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Payment\PaymentModeResource;

class CashRegisterTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'payment_mode_id' => $this->payment_mode_id,

            'payment_mode' => new PaymentModeResource(
                $this->whenLoaded('paymentMode')
            ),

            'type' => $this->type,

            'amount' => $this->amount,

            'reference_type' => $this->reference_type,

            'reference_id' => $this->reference_id,

            'transaction_at' => $this->transaction_at,

            'remarks' => $this->remarks,

        ];
    }
}
