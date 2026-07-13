<?php

declare(strict_types=1);

namespace App\Models\POS;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegister extends Model
{
    use SoftDeletes;

    protected $fillable = [

        'register_no',

        'name',

        'user_id',

        'opening_balance',

        'opened_at',

        'closing_balance',

        'closed_at',

        'status',

        'remarks',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'opening_balance' => 'decimal:2',

        'closing_balance' => 'decimal:2',

        'opened_at' => 'datetime',

        'closed_at' => 'datetime',

    ];

    /**
     * Cashier
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sessions
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(CashRegisterSession::class);
    }

    /**
     * Creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * Updater
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}
