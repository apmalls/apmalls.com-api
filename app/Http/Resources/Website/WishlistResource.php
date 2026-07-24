<?php

declare(strict_types=1);

namespace App\Http\Resources\Website;

use App\Http\Resources\Product\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    /**
     * Transform resource.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'remarks' => $this->remarks,

            'created_at' => $this->created_at,

            'product' => new ProductResource(
                $this->whenLoaded('product')
            ),

        ];

    }
}
