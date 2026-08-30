<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Customer\Customer;
use App\Models\Delivery\DeliveryBoy;
use App\Traits\HasMedia;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[Fillable([
    'first_name',
    'last_name',
    'username',
    'email',
    'mobile',
    'password',
    'profile_photo',
    'is_active',
    'terms_accepted',
    'terms_accepted_at',
    'terms_version'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */

    use HasFactory,
        HasApiTokens,
        HasRoles,
        LogsActivity,
        Notifiable,
        SoftDeletes, HasMedia;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'terms_accepted' => 'boolean',
            'terms_accepted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()

            ->logOnly([
                'first_name',
                'last_name',
                'email',
                'mobile',
                'is_active',
                'terms_accepted',
                'terms_accepted_at',
                'terms_version'
            ])

            ->logOnlyDirty();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    protected $appends = [
        'full_name',
        'profile_photo_url',
    ];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->fileUrl($this->profile_photo);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function deliveryBoy(): HasOne
    {
        return $this->hasOne(DeliveryBoy::class);
    }
}
