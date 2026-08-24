<?php

namespace App\Models\Payment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PaymentStatusHistory extends Model
{


    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
