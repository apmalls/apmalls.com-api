<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashRegisterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'register_no' => $this->register_no,

            'name' => $this->name,

            'opening_balance' => $this->opening_balance,

            'closing_balance' => $this->closing_balance,

            'status' => $this->status,

            'opened_at' => $this->opened_at,

            'closed_at' => $this->closed_at,

            'remarks' => $this->remarks,

            'user' => $this->whenLoaded('user'),

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}
