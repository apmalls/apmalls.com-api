<?php

namespace App\Models\Sale;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use App\Models\Payment\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleOrder extends Model
{
    use HasFactory, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_COMPLETED = 'completed';
    public const PAYMENT_REFUNDED = 'refunded';

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'customer_id',

        'sale_no',

        'invoice_no',

        'sale_date',

        'invoice_date',

        'sub_total',

        'discount_amount',

        'tax_amount',

        'shipping_amount',

        'other_amount',

        'round_off',

        'grand_total',

        'paid_amount',

        'due_amount',

        'refund_amount',

        'payment_status',

        'status',

        'remarks',

        'billing_address_id',

        'shipping_address_id',

        'created_by',

        'updated_by',

    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'sale_date' => 'date',

        'invoice_date' => 'date',

        'sub_total' => 'decimal:2',

        'discount_amount' => 'decimal:2',

        'tax_amount' => 'decimal:2',

        'shipping_amount' => 'decimal:2',

        'other_amount' => 'decimal:2',

        'round_off' => 'decimal:2',

        'grand_total' => 'decimal:2',

        'paid_amount' => 'decimal:2',

        'due_amount' => 'decimal:2',

        'refund_amount' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Appends
    |--------------------------------------------------------------------------
    */

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

    public function billingAddress()
    {
        return $this->belongsTo(
            CustomerAddress::class,
            'billing_address_id'
        );
    }

    public function shippingAddress()
    {
        return $this->belongsTo(
            CustomerAddress::class,
            'shipping_address_id'
        );
    }

    public function items()
    {
        return $this->hasMany(SaleOrderItem::class);
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class);
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
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTotalItemsAttribute(): int
    {
        return (int) $this->items()->sum('quantity');
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

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
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

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPendingPayment(): bool
    {
        return $this->payment_status === self::PAYMENT_PENDING;
    }

    public function isCompletedPayment(): bool
    {
        return $this->payment_status === self::PAYMENT_COMPLETED;
    }
}
