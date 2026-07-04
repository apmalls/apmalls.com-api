<?php

namespace App\Models\Purchase;


use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [

        'purchase_return_id',

        'purchase_order_item_id',

        'product_id',

        'purchase_price',

        'quantity',

        'line_total',

    ];

    protected $casts = [

        'purchase_price' => 'decimal:2',

        'quantity' => 'integer',

        'line_total' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
