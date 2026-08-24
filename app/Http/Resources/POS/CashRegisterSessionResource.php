<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashRegisterSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'session_no' => $this->session_no,

            'opening_balance' => $this->opening_balance,

            'expected_balance' => $this->expected_balance,

            'closing_balance' => $this->closing_balance,

            'difference' => $this->difference,

            'status' => $this->status,

            'opened_at' => $this->opened_at,

            'closed_at' => $this->closed_at,

            'remarks' => $this->remarks,

            'register' => new CashRegisterResource(
                $this->whenLoaded('cashRegister')
            ),

            'transactions' => CashRegisterTransactionResource::collection(
                $this->whenLoaded('transactions')
            ),

        ];
    }
}
