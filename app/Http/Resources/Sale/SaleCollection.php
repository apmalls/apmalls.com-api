<?php

namespace App\Http\Resources\Sale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SaleCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'data' => SaleResource::collection($this->collection),

        ];
    }

    /**
     * Additional meta data.
     */
    public function with(Request $request): array
    {
        return [

            'success' => true,

            'message' => 'Sales retrieved successfully.',

        ];
    }
}
