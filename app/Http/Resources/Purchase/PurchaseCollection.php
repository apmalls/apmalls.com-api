<?php

namespace App\Http\Resources\Purchase;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'success' => true,

            'message' => 'Purchase list retrieved successfully.',

            'data' => PurchaseResource::collection(
                $this->collection
            ),

        ];
    }

    /**
     * Additional meta data.
     */
    public function with(Request $request): array
    {
        return [

            'meta' => [

                'current_page' => $this->currentPage(),

                'from' => $this->firstItem(),

                'last_page' => $this->lastPage(),

                'per_page' => $this->perPage(),

                'to' => $this->lastItem(),

                'total' => $this->total(),

            ],

            'links' => [

                'first' => $this->url(1),

                'last' => $this->url($this->lastPage()),

                'prev' => $this->previousPageUrl(),

                'next' => $this->nextPageUrl(),

            ],

        ];
    }
}
