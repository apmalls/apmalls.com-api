<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMode extends Model
{

    use HasFactory, SoftDeletes;

    protected $fillable = [

        'name',

        'code',

        'description',

        'icon',

        'is_online',

        'is_active',

        'sort_order',

    ];

    protected $casts = [

        'is_online' => 'boolean',

        'is_active' => 'boolean',

    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeOffline($query)
    {
        return $query->where('is_online', false);
    }
}
