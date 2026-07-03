<?php

namespace App\Models\Purchase;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        'supplier_id',

        'purchase_no',

        'invoice_no',

        'purchase_date',

        'invoice_date',

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

        'purchase_date' => 'date',

        'invoice_date' => 'date',

        'sub_total' => 'decimal:2',

        'discount_amount' => 'decimal:2',

        'tax_amount' => 'decimal:2',

        'shipping_charge' => 'decimal:2',

        'other_charge' => 'decimal:2',

        'grand_total' => 'decimal:2',

        'paid_amount' => 'decimal:2',

        'due_amount' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
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
