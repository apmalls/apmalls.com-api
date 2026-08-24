<?php

declare(strict_types=1);

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

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

        'amount' => 'decimal:2',

    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function paymentMode(): BelongsTo
    {
        return $this->belongsTo(PaymentMode::class);
    }
}
