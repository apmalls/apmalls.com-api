<?php

namespace App\Models\Purchase;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Relations
        |--------------------------------------------------------------------------
        */

        'purchase_order_id',

        'product_id',

        'unit_id',

        /*
        |--------------------------------------------------------------------------
        | Quantity
        |--------------------------------------------------------------------------
        */

        'quantity',

        'received_quantity',

        'free_quantity',

        /*
        |--------------------------------------------------------------------------
        | Pricing
        |--------------------------------------------------------------------------
        */

        'unit_cost',

        'tax_percent',

        'tax_amount',

        'discount_percent',

        'discount_amount',

        'line_total',

    ];

    protected $casts = [

        'quantity' => 'decimal:2',

        'received_quantity' => 'decimal:2',

        'free_quantity' => 'decimal:2',

        'unit_cost' => 'decimal:2',

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

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function getPendingQuantityAttribute(): float
    {
        return max(
            0,
            (float) $this->quantity - (float) $this->received_quantity
        );
    }

    public function getReceivedPercentageAttribute(): float
    {
        if ((float) $this->quantity <= 0) {
            return 0;
        }

        return round(
            ((float) $this->received_quantity / (float) $this->quantity) * 100,
            2
        );
    }

    public function isFullyReceived(): bool
    {
        return (float) $this->received_quantity >= (float) $this->quantity;
    }

    public function isPartiallyReceived(): bool
    {
        return (float) $this->received_quantity > 0
            && (float) $this->received_quantity < (float) $this->quantity;
    }

    public function isPending(): bool
    {
        return (float) $this->received_quantity == 0;
    }
}
