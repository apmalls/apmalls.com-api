<?php

declare(strict_types=1);

namespace App\Models\POS;

use App\Models\Payment\PaymentMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterTransaction extends Model
{
    protected $fillable = [

        'cash_register_session_id',

        'module',

        'module_id',

        'payment_mode_id',

        'type',

        'amount',

        'transaction_at',

        'remarks',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'amount' => 'decimal:2',

        'transaction_at' => 'datetime',

    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            CashRegisterSession::class,
            'cash_register_session_id'
        );
    }

    public function paymentMode(): BelongsTo
    {
        return $this->belongsTo(
            PaymentMode::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}
