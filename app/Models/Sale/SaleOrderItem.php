<?php

namespace App\Models\Sale;

use App\Models\Product\Product;
use App\Models\Product\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleOrderItem extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'sale_order_id',

        'product_id',

        'unit_id',

        'quantity',

        'returned_quantity',

        'purchase_price',

        'selling_price',

        'tax_percent',

        'tax_amount',

        'discount_percent',

        'discount_amount',

        'line_total',

    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'purchase_price' => 'decimal:2',

        'selling_price' => 'decimal:2',

        'quantity' => 'integer',

        'returned_quantity' => 'integer',

        'tax_percent' => 'decimal:2',

        'tax_amount' => 'decimal:2',

        'discount_percent' => 'decimal:2',

        'discount_amount' => 'decimal:2',

        'line_total' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function saleReturnItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getPendingQuantityAttribute(): int
    {
        return max(
            0,
            $this->quantity - $this->returned_quantity
        );
    }

    public function getReturnedPercentageAttribute(): float
    {
        if ($this->quantity == 0) {
            return 0;
        }

        return round(
            ($this->returned_quantity / $this->quantity) * 100,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function calculateLineTotal(): float
    {
        return round(

            (($this->selling_price * $this->quantity)
                + $this->tax_amount)
            - $this->discount_amount,

            2

        );
    }

    public function getTotalAttribute(): float
    {
        return $this->calculateLineTotal();
    }

    public function isFullyReturned(): bool
    {
        return $this->returned_quantity >= $this->quantity;
    }

    public function isPartiallyReturned(): bool
    {
        return $this->returned_quantity > 0
            && $this->returned_quantity < $this->quantity;
    }

    public function isPending(): bool
    {
        return $this->returned_quantity == 0;
    }
}
