<?php

namespace App\Models\Customer;

use App\Models\Sale\SaleOrder;
use App\Models\Sale\SaleReturnItem;
use App\Models\User;
use App\Models\Wishlist\Wishlist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        'user_id',

        'customer_code',

        'customer_type',

        'first_name',

        'last_name',

        'mobile',

        'alternate_mobile',

        'email',

        'company_name',

        'gst_number',

        'date_of_birth',

        'anniversary_date',

        'address',

        'city',

        'state',

        'country',

        'pincode',

        'opening_balance',

        'credit_limit',

        'reward_points',

        'notes',

        'is_active',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'date_of_birth' => 'date',

        'anniversary_date' => 'date',

        'opening_balance' => 'decimal:2',

        'credit_limit' => 'decimal:2',

        'reward_points' => 'integer',

        'is_active' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function saleOrders()
    {
        return $this->hasMany(SaleOrder::class);
    }

    public function saleReturnItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function wishlists()
    {
        return $this->hasMany(
            Wishlist::class
        );
    }
}
