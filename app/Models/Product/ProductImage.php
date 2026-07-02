<?php

namespace App\Models\Product;

use App\Models\Product;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductImage extends Model
{
    use HasFactory, HasMedia;

    protected $fillable = [
        'product_id',
        'image',
        'sort_order',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->fileUrl($this->image);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
