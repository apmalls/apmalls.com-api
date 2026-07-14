<?php

namespace App\Models\Product;

use App\Models\Inventory\Stock;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockMovement;
use App\Models\Product\Brand;
use App\Models\Category\Category;
use App\Models\Product\ProductImage;
use App\Models\Product\Unit;
use App\Models\Purchase\PurchaseReturnItem;
use App\Models\Sale\SaleOrderItem;
use App\Models\User;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasMedia;

    protected $fillable = [
        'category_id',
        'brand_id',
        'unit_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'hsn_code',
        'thumbnail',
        'short_description',
        'description',
        'purchase_price',
        'selling_price',
        'mrp',
        'tax_percent',
        'discount_percent',
        'stock',
        'minimum_stock',
        'featured',
        'new_arrival',
        'best_seller',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'featured' => 'boolean',
        'new_arrival' => 'boolean',
        'best_seller' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'thumbnail_url',
    ];

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->fileUrl($this->thumbnail);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function stock()
    {
        return $this->hasOne(Stock::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleOrderItem::class);
    }

    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where(
            'is_active',
            true
        );
    }

    public function scopeFeatured($query)
    {
        return $query->where(
            'featured',
            true
        );
    }

    public function scopeNewArrival($query)
    {
        return $query->where(
            'new_arrival',
            true
        );
    }

    public function scopeBestSeller($query)
    {
        return $query->where(
            'best_seller',
            true
        );
    }
}
