<?php

namespace App\Models\Sale;

use App\Models\Customer\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        'customer_id',

        'sale_no',

        'invoice_no',

        'sale_date',

        'sub_total',

        'discount_amount',

        'tax_amount',

        'shipping_charge',

        'other_charge',

        'grand_total',

        'paid_amount',

        'due_amount',

        'status',

        'remarks',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'sale_date' => 'date',

        'sub_total' => 'decimal:2',

        'discount_amount' => 'decimal:2',

        'tax_amount' => 'decimal:2',

        'shipping_charge' => 'decimal:2',

        'other_charge' => 'decimal:2',

        'grand_total' => 'decimal:2',

        'paid_amount' => 'decimal:2',

        'due_amount' => 'decimal:2',

    ];

    protected $appends = [
        'total_items',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items()->sum('quantity');
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class);
    }
}
