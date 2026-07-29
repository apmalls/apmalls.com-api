<?php

declare(strict_types=1);

namespace App\Models\Delivery;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryBoy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_code',
        'phone',
        'alternate_phone',
        'vehicle_type',
        'vehicle_number',
        'license_number',
        'aadhaar_no',
        'pan_no',
        'photo',
        'address',
        'current_latitude',
        'current_longitude',
        'is_available',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'current_latitude' => 'decimal:7',
            'current_longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Delivery Assignments
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class);
    }
}
