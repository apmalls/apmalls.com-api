<?php

declare(strict_types=1);

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;

class OrderEmailDelivery extends Model
{
    protected $guarded = [];

    protected $hidden = ['recipient', 'snapshot', 'version'];

    protected function casts(): array
    {
        return [
            'recipient' => 'encrypted',
            'snapshot' => 'encrypted:array',
            'attempts' => 'integer',
            'recipient_user_id' => 'integer',
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
