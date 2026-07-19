<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMode extends Model
{

    use HasFactory;

    protected $fillable = [

        'name',

        'code',

        'is_active',

        'sort_order',

    ];

    protected $casts = [

        'is_active' => 'boolean',

        'sort_order' => 'integer',

    ];



    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function gatewayTransactions()
    {
        return $this->hasMany(PaymentGatewayTransaction::class);
    }
}
