<?php

namespace App\Models\OTP;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $fillable = [

        'type',

        'channel',

        'recipient',

        'otp',

        'expires_at',

        'is_verified',

        'verified_at',

        'attempts',

        'max_attempts',

        'ip_address',

        'user_agent',

    ];

    protected $casts = [

        'expires_at' => 'datetime',

        'verified_at' => 'datetime',

        'is_verified' => 'boolean',

    ];
}
