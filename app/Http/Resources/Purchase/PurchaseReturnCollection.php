<?php

namespace App\Http\Resources\Purchase;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseReturnCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'data' => PurchaseReturnResource::collection(
                $this->collection
            ),

        ];
    }

    /**
     * Additional response data.
     */
    public function with(
        Request $request
    ): array {

        return [

            'success' => true,

            'message' => 'Purchase Returns fetched successfully.',

        ];
    }
}
