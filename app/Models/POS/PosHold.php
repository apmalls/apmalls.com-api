<?php

declare(strict_types=1);

namespace App\Models\POS;

use App\Models\Customer\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosHold extends Model
{
    use SoftDeletes;

    protected $fillable = [

        'hold_no',

        'cash_register_session_id',

        'customer_id',

        'sub_total',

        'discount',

        'tax',

        'grand_total',

        'status',

        'remarks',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'sub_total' => 'decimal:2',

        'discount' => 'decimal:2',

        'tax' => 'decimal:2',

        'grand_total' => 'decimal:2',

    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            CashRegisterSession::class,
            'cash_register_session_id'
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosHoldItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class,'updated_by');
    }
}
