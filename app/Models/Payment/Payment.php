<?php

namespace App\Models\Payment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{

    use HasFactory, SoftDeletes;

    protected $fillable = [

        'payment_no',

        'payment_date',

        'module',

        'module_id',

        'payment_mode_id',

        'amount',

        'transaction_no',

        'reference_no',

        'status',

        'remarks',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'payment_date' => 'date',

        'amount' => 'decimal:2',

    ];


    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function gatewayTransaction()
    {
        return $this->hasOne(PaymentGatewayTransaction::class);
    }
}
