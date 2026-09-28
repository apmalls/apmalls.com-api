<?php

namespace App\Models\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginChallenge extends Model
{
    protected $fillable = [
        'challenge_id',
        'user_id',
        'otp_hash',
        'expires_at',
        'resend_available_at',
        'attempts',
        'max_attempts',
        'send_count',
        'consumed_at',
        'invalidated_at',
        'ip_address',
        'user_agent',
    ];

    protected $hidden = ['otp_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'consumed_at' => 'datetime',
            'invalidated_at' => 'datetime',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'send_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
