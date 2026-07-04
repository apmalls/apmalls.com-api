<?php

namespace App\Models\Sale;


use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [

        'sale_return_id',

        'sale_order_item_id',

        'product_id',

        'selling_price',

        'quantity',

        'line_total',

    ];

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
}
