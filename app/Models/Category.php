<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory, SoftDeletes, HasMedia;

    protected $fillable = [

    'parent_id',

    'name',

    'slug',

    'description',

    'image',

    'sort_order',

    'is_active',

    'created_by',

    'updated_by'

];

    protected $casts = [

        'is_active' => 'boolean'

    ];

    protected $appends = [

        'image_url'

    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->fileUrl($this->image);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
