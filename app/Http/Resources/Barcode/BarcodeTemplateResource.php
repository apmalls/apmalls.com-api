<?php

namespace App\Http\Resources\Barcode;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarcodeTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'name' => $this->name,

            'paper_size' => $this->paper_size,

            'width' => $this->width,

            'height' => $this->height,

            'font_size' => $this->font_size,

            'show_name' => (bool) $this->show_name,

            'show_price' => (bool) $this->show_price,

            'show_sku' => (bool) $this->show_sku,

            'show_barcode' => (bool) $this->show_barcode,

            'show_qr' => (bool) $this->show_qr,

            'status' => (bool) $this->status,

            'created_at' => optional($this->created_at)->format('d-m-Y H:i:s'),

            'updated_at' => optional($this->updated_at)->format('d-m-Y H:i:s'),

        ];
    }
}
