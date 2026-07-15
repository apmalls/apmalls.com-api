<?php

declare(strict_types=1);

namespace App\Models\Cart;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [

        'cart_id',

        'product_id',

        'quantity',

        'price',

        'tax_percent',

        'tax_amount',

        'discount_percent',

        'discount_amount',

        'subtotal',

    ];

    protected $casts = [

        'price' => 'decimal:2',

        'tax_percent' => 'decimal:2',

        'tax_amount' => 'decimal:2',

        'discount_percent' => 'decimal:2',

        'discount_amount' => 'decimal:2',

        'subtotal' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Cart
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(
            Cart::class
        );
    }

    /**
     * Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }
}
