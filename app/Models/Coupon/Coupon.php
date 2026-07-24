<?php

namespace App\Models\Coupon;

use Illuminate\Database\Eloquent\Model;

use App\Models\Cart\Cart;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [

        'name',

        'code',

        'discount_type',

        'discount_value',

        'minimum_order_amount',

        'maximum_discount_amount',

        'usage_limit',

        'used_count',

        'start_at',

        'end_at',

        'is_active',

        'remarks',

    ];

    protected $casts = [

        'discount_value' => 'decimal:2',

        'minimum_order_amount' => 'decimal:2',

        'maximum_discount_amount' => 'decimal:2',

        'start_at' => 'datetime',

        'end_at' => 'datetime',

        'is_active' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function carts(): HasMany
    {
        return $this->hasMany(
            Cart::class
        );
    }
}
