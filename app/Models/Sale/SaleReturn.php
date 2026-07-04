<?php

namespace App\Models\Sale;

use App\Models\Customer\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        'sale_order_id',

        'customer_id',

        'return_no',

        'return_date',

        'total_amount',

        'remarks',

        'status',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'return_date' => 'date',

        'total_amount' => 'decimal:2',

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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
