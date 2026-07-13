<?php

declare(strict_types=1);

namespace App\Models\POS;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterSession extends Model
{
    use SoftDeletes;

    protected $fillable = [

        'session_no',

        'cash_register_id',

        'user_id',

        'opening_balance',

        'closing_balance',

        'expected_balance',

        'difference',

        'opened_at',

        'closed_at',

        'status',

        'remarks',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'opening_balance' => 'decimal:2',

        'closing_balance' => 'decimal:2',

        'expected_balance' => 'decimal:2',

        'difference' => 'decimal:2',

        'opened_at' => 'datetime',

        'closed_at' => 'datetime',

    ];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            CashRegisterTransaction::class
        );
    }

    public function holds(): HasMany
    {
        return $this->hasMany(PosHold::class);
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
