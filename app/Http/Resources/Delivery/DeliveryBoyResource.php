<?php

declare(strict_types=1);

namespace App\Http\Resources\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Delivery\DeliveryBoy
 */
class DeliveryBoyResource extends JsonResource
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

            'user' => [
                'id' => $this->user?->id,
                'first_name' => $this->user?->first_name,
                'last_name' => $this->user?->last_name,
                'email' => $this->user?->email,
            ],

            'employee_code' => $this->employee_code,

            'phone' => $this->phone,

            'alternate_phone' => $this->alternate_phone,

            'vehicle_type' => $this->vehicle_type,

            'vehicle_number' => $this->vehicle_number,

            'license_number' => $this->license_number,

            'aadhaar_no' => $this->aadhaar_no,

            'pan_no' => $this->pan_no,

            'photo' => $this->photo,

            'address' => $this->address,

            'current_latitude' => $this->current_latitude,

            'current_longitude' => $this->current_longitude,

            'is_available' => $this->is_available,

            'is_active' => $this->is_active,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}
