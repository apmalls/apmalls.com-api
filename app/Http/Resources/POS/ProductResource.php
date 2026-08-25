<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'selling_price' => $this->selling_price,
            'purchase_price' => $this->purchase_price,
            'unit_id' => $this->unit_id,
            'tax_percent' => $this->tax_percent,
            'discount_percent' => $this->discount_percent,
        ];
    }
}
