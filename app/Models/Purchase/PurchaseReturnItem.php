<?php

namespace App\Models\Purchase;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Relations
        |--------------------------------------------------------------------------
        */

        'purchase_return_id',

        'purchase_order_item_id',

        'product_id',

        /*
        |--------------------------------------------------------------------------
        | Pricing
        |--------------------------------------------------------------------------
        */

        'unit_cost',

        /*
        |--------------------------------------------------------------------------
        | Quantity
        |--------------------------------------------------------------------------
        */

        'quantity',

        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        'line_total',

    ];

    protected $casts = [

        'unit_cost' => 'decimal:2',

        'quantity' => 'decimal:2',

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

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function getTotalAttribute(): float
    {
        return (float) $this->line_total;
    }

    public function calculateLineTotal(): float
    {
        return round(
            (float) $this->unit_cost * (float) $this->quantity,
            2
        );
    }

    public function isFullReturn(): bool
    {
        if (!$this->purchaseOrderItem) {
            return false;
        }

        return (float) $this->quantity >= (float) $this->purchaseOrderItem->received_quantity;
    }

    public function isPartialReturn(): bool
    {
        if (!$this->purchaseOrderItem) {
            return false;
        }

        return (float) $this->quantity > 0
            && (float) $this->quantity < (float) $this->purchaseOrderItem->received_quantity;
    }
}
