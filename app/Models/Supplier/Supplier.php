<?php

namespace App\Models\Supplier;

use App\Models\Purchase\PurchaseReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        'user_id',

        'supplier_code',

        'company_name',

        'contact_person',

        'mobile',

        'alternate_mobile',

        'email',

        'gst_number',

        'pan_number',

        'address',

        'city',

        'state',

        'country',

        'pincode',

        'bank_name',

        'account_holder_name',

        'account_number',

        'ifsc_code',

        'opening_balance',

        'credit_limit',

        'notes',

        'is_active',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'opening_balance' => 'decimal:2',

        'credit_limit' => 'decimal:2',

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
        return $this->hasMany(SupplierAddress::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

}
