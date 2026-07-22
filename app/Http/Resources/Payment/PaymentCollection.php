<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'data' => PaymentResource::collection($this->collection),

        ];
    }

    /**
     * Additional response data.
     */
    public function with(Request $request): array
    {
        return [

            'success' => true,

            'message' => 'Payments fetched successfully.',

        ];
    }
}
