<?php

declare(strict_types=1);

namespace App\Models\POS;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosHoldItem extends Model
{
    protected $fillable = [

        'pos_hold_id',

        'product_id',

        'quantity',

        'price',

        'discount',

        'tax',

        'total',

    ];

    protected $casts = [

        'quantity' => 'decimal:2',

        'price' => 'decimal:2',

        'discount' => 'decimal:2',

        'tax' => 'decimal:2',

        'total' => 'decimal:2',

    ];

    public function hold(): BelongsTo
    {
        return $this->belongsTo(PosHold::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
