<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SaleReturnCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'data' => SaleReturnResource::collection(
                $this->collection
            ),

        ];
    }

    public function with(
        Request $request
    ): array {

        return [

            'success' => true,

            'message' => 'Sale Returns fetched successfully.',

        ];
    }
}
