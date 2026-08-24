<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'payment_no' => $this->payment_no,

            'payment_date' => optional($this->payment_date)->format('Y-m-d'),

            'amount' => $this->amount,

            'status' => $this->status,

            'transaction_no' => $this->transaction_no,

            'reference_no' => $this->reference_no,

            'gateway' => $this->gateway,

            'remarks' => $this->remarks,

            'payment_mode' => $this->whenLoaded('paymentMode', function () {

                return [

                    'id' => $this->paymentMode->id,

                    'name' => $this->paymentMode->name,

                    'code' => $this->paymentMode->code,

                ];

            }),

            'customer' => $this->whenLoaded('customer', function () {

                return [

                    'id' => $this->customer->id,

                    'name' => $this->customer->name,

                ];

            }),

            'supplier' => $this->whenLoaded('supplier', function () {

                return [

                    'id' => $this->supplier->id,

                    'name' => $this->supplier->name,

                ];

            }),

            'created_by' => $this->whenLoaded('createdBy', function () {

                return [

                    'id' => $this->createdBy->id,

                    'name' => $this->createdBy->name,

                ];

            }),

            'updated_by' => $this->whenLoaded('updatedBy', function () {

                return [

                    'id' => $this->updatedBy->id,

                    'name' => $this->updatedBy->name,

                ];

            }),

            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),

        ];
    }
}
