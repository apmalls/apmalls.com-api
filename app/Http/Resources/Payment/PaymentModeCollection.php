<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentModeCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'data' => PaymentModeResource::collection($this->collection),

        ];
    }

    public function with(Request $request): array
    {
        return [

            'success' => true,

            'message' => 'Payment modes fetched successfully.',

        ];
    }
}
