<?php

namespace App\Models\Purchase;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [

        'purchase_order_id',

        'product_id',

        'purchase_price',

        'selling_price',

        'quantity',

        'received_quantity',

        'tax_percent',

        'tax_amount',

        'discount_percent',

        'discount_amount',

        'line_total',

    ];

    protected $casts = [

        'purchase_price' => 'decimal:2',

        'selling_price' => 'decimal:2',

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

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
