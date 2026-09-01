<?php

declare(strict_types=1);

namespace App\Models\Delivery;

use App\Models\Customer\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeliveryConfirmation extends Model
{
    public const STATUS_AWAITING_CUSTOMER = 'awaiting_customer';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_RESOLVED_CONFIRMED = 'resolved_confirmed';
    public const STATUS_RESOLVED_REOPENED = 'resolved_reopened';
    public const STATUS_LEGACY_COMPLETED = 'legacy_completed';

    public const METHOD_APP = 'app';
    public const METHOD_OTP = 'otp';
    public const METHOD_MANAGER = 'manager';
    public const METHOD_LEGACY = 'legacy';

    protected $fillable = [
        'delivery_assignment_id', 'customer_id', 'status',
        'delivery_reported_by', 'delivery_reported_at', 'courier_remarks',
        'cash_collected_reported', 'cash_amount_reported',
        'customer_confirmed_by', 'customer_confirmed_at', 'customer_confirmed_amount',
        'payment_confirmed_at', 'confirmation_method',
        'otp_hash', 'otp_expires_at', 'otp_attempts', 'otp_max_attempts',
        'disputed_by', 'disputed_at', 'dispute_reason',
        'resolved_by', 'resolved_at', 'resolution_remarks',
    ];

    protected $hidden = ['otp_hash'];

    protected function casts(): array
    {
        return [
            'delivery_reported_at' => 'datetime',
            'cash_collected_reported' => 'boolean',
            'cash_amount_reported' => 'decimal:2',
            'customer_confirmed_at' => 'datetime',
            'customer_confirmed_amount' => 'decimal:2',
            'payment_confirmed_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'otp_attempts' => 'integer',
            'otp_max_attempts' => 'integer',
            'disputed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function assignment()
    {
        return $this->belongsTo(DeliveryAssignment::class, 'delivery_assignment_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryReportedBy()
    {
        return $this->belongsTo(User::class, 'delivery_reported_by');
    }

    public function customerConfirmedBy()
    {
        return $this->belongsTo(User::class, 'customer_confirmed_by');
    }

    public function disputedBy()
    {
        return $this->belongsTo(User::class, 'disputed_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
