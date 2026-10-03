<?php

namespace App\Models\Offer;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class WebsiteOffer extends Model
{
    use HasFactory, SoftDeletes;

    public const DISPLAY_FULL_IMAGE = 'full_image';
    public const DISPLAY_IMAGE_WITH_TEXT = 'image_with_text';

    protected $attributes = [
        'display_mode' => self::DISPLAY_IMAGE_WITH_TEXT,
        'position' => 'offer',
    ];

    protected $fillable = [
        'title',
        'sub_title',
        'slug',
        'description',
        'desktop_image',
        'mobile_image',
        'type',
        'display_mode',
        'video_url',
        'position',
        'button_text',
        'button_url',
        'open_new_tab',
        'sort_order',
        'status',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
        'open_new_tab' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        $path = $this->desktop_image ?: $this->mobile_image;

        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::url($path);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        $businessNow = Carbon::now(config('app.business_timezone'))
            ->format('Y-m-d H:i:s');

        return $query
            ->active()
            ->where(function ($q) use ($businessNow) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $businessNow);
            })
            ->where(function ($q) use ($businessNow) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $businessNow);
            });
    }

    public function publicationStatus(): string
    {
        if (! $this->status) {
            return 'inactive';
        }

        $timezone = config('app.business_timezone');
        $businessNow = Carbon::now($timezone);
        $startDate = $this->businessScheduleDate('start_date', $timezone);
        $endDate = $this->businessScheduleDate('end_date', $timezone);

        if ($startDate?->isAfter($businessNow)) {
            return 'scheduled';
        }

        if ($endDate?->isBefore($businessNow)) {
            return 'expired';
        }

        return 'published';
    }

    private function businessScheduleDate(string $attribute, string $timezone): ?Carbon
    {
        $value = $this->getRawOriginal($attribute);

        return $value ? Carbon::parse($value, $timezone) : null;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->latest('id');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $query->where(function ($q) use ($search, $operator) {
            $q->where('title', $operator, "%{$search}%")
                ->orWhere('sub_title', $operator, "%{$search}%")
                ->orWhere('slug', $operator, "%{$search}%");
        });
    }
}
