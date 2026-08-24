<?php

namespace App\Models\POS;

use App\Models\Payment\PaymentMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterTransaction extends Model
{
    public const TYPE_IN = 'In';
    public const TYPE_OUT = 'Out';

    public const TYPE_CASH_IN = 'cash_in';

    public const TYPE_CASH_OUT = 'cash_out';

    protected $fillable = [
        'cash_register_session_id',
        reference_type .
        reference_id,
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
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }

    public function paymentMode(): BelongsTo
    {
        return $this->belongsTo(PaymentMode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
