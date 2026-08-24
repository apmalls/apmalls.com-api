<?php

namespace App\Models\Purchase;

use App\Models\Payment\Payment;
use App\Models\Supplier\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Relations
        |--------------------------------------------------------------------------
        */

        'supplier_id',
        'warehouse_id',

        /*
        |--------------------------------------------------------------------------
        | Document Details
        |--------------------------------------------------------------------------
        */

        'purchase_no',
        'invoice_no',
        'purchase_date',
        'invoice_date',

        /*
        |--------------------------------------------------------------------------
        | Amounts
        |--------------------------------------------------------------------------
        */

        'sub_total',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'other_amount',
        'round_off',
        'grand_total',

        /*
        |--------------------------------------------------------------------------
        | Payment Summary
        |--------------------------------------------------------------------------
        */

        'paid_amount',
        'due_amount',
        'refund_amount',
        'payment_status',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'status',
        'remarks',

        /*
        |--------------------------------------------------------------------------
        | Audit
        |--------------------------------------------------------------------------
        */

        'created_by',
        'updated_by',
    ];

    protected $casts = [

        'purchase_date' => 'date',
        'invoice_date'  => 'date',

        'sub_total'       => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'other_amount'    => 'decimal:2',
        'round_off'       => 'decimal:2',
        'grand_total'     => 'decimal:2',

        'paid_amount'     => 'decimal:2',
        'due_amount'      => 'decimal:2',
        'refund_amount'   => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Purchase Status
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public static function statuses(): array
    {
        return [

            self::STATUS_DRAFT,

            self::STATUS_ORDERED,

            self::STATUS_PARTIAL,

            self::STATUS_RECEIVED,

            self::STATUS_COMPLETED,

            self::STATUS_CANCELLED,

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_COMPLETED = 'completed';

    public const PAYMENT_REFUNDED = 'refunded';

    public static function paymentStatuses(): array
    {
        return [

            self::PAYMENT_PENDING,

            self::PAYMENT_PARTIAL,

            self::PAYMENT_COMPLETED,

            self::PAYMENT_REFUNDED,

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // public function warehouse()
    // {
    //     return $this->belongsTo(Warehouse::class);
    // }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function payments()
    {
        return $this->morphMany(
            Payment::class,
            'paymentable'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater()
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', self::STATUS_RECEIVED);
    }

    public function scopePendingPayment($query)
    {
        return $query->where(
            'payment_status',
            self::PAYMENT_PENDING
        );
    }

    public function scopeCompletedPayment($query)
    {
        return $query->where(
            'payment_status',
            self::PAYMENT_COMPLETED
        );
    }
}
