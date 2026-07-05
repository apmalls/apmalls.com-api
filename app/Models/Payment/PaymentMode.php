<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class PaymentMode extends Model
{


    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
