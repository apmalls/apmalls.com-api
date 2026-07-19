<?php

declare(strict_types=1);

namespace App\Models\Payment;

use App\Models\Sale\SaleOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGatewayTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [

        'sale_order_id',

        'payment_id',

        'payment_mode_id',

        'gateway',

        'gateway_order_id',

        'gateway_payment_id',

        'gateway_signature',

        'gateway_status',

        'amount',

        'currency',

        'request_payload',

        'response_payload',

        'paid_at',

    ];

    protected $casts = [

        'request_payload' => 'array',

        'response_payload' => 'array',

        'paid_at' => 'datetime',

    ];

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class);
    }

}
