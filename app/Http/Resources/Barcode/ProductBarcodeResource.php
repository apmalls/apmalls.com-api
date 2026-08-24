<?php

namespace App\Http\Resources\Barcode;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBarcodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'product_id' => $this['product_id'],

            'name' => $this['name'],

            'sku' => $this['sku'],

            'barcode' => $this['barcode'],

            'barcode_type' => $this['barcode_type'],

            'svg' => $this['svg'],

            'png' => $this['png'],

            'html' => $this['html'],

        ];
    }
}
