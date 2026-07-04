<?php

namespace App\Models\Sale;


use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [

        'sale_order_id',

        'product_id',

        'purchase_price',

        'selling_price',

        'quantity',

        'tax_percent',

        'tax_amount',

        'discount_percent',

        'discount_amount',

        'line_total',

    ];

    protected $casts = [

        'purchase_price' => 'decimal:2',

        'selling_price' => 'decimal:2',

        'quantity' => 'integer',

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

    public function saleReturnItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
