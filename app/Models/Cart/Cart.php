<?php

declare(strict_types=1);

namespace App\Models\Cart;

use App\Models\Coupon\Coupon;
use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [

        'cart_no',

        'customer_id',

        'coupon_id',

        'coupon_code',

        'subtotal',

        'discount_amount',

        'tax_amount',

        'shipping_charge',

        'grand_total',

        'status',

        'remarks',

    ];

    protected $casts = [

        'subtotal' => 'decimal:2',

        'discount_amount' => 'decimal:2',

        'tax_amount' => 'decimal:2',

        'shipping_charge' => 'decimal:2',

        'grand_total' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            Customer::class
        );
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(
            Coupon::class
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            CartItem::class
        )->orderBy('id');
    }
}
