<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentModeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'name' => $this->name,

            'code' => $this->code,

            'description' => $this->description,

            'icon' => $this->icon
                ? asset('storage/' . $this->icon)
                : null,

            'is_online' => (bool) $this->is_online,

            'is_active' => (bool) $this->is_active,

            'sort_order' => $this->sort_order,

            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),

        ];
    }
}
