<?php

namespace App\Models\Sale;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'sale_return_id',

        'sale_order_item_id',

        'product_id',

        'selling_price',

        'quantity',

        'line_total',

    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'selling_price' => 'decimal:2',

        'quantity' => 'integer',

        'line_total' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleOrderItem()
    {
        return $this->belongsTo(SaleOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTotalAttribute(): float
    {
        return $this->calculateLineTotal();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function calculateLineTotal(): float
    {
        return round(
            $this->selling_price * $this->quantity,
            2
        );
    }

    public function isFullReturn(): bool
    {
        if (!$this->saleOrderItem) {
            return false;
        }

        return $this->quantity >= $this->saleOrderItem->quantity;
    }

    public function isPartialReturn(): bool
    {
        if (!$this->saleOrderItem) {
            return false;
        }

        return $this->quantity > 0
            && $this->quantity < $this->saleOrderItem->quantity;
    }
}
