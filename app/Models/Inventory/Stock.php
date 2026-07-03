<?php

namespace App\Models\Inventory;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
   use HasFactory;

    protected $fillable = [

        'product_id',

        'current_stock',

        'reserved_stock',

        'available_stock',

        'minimum_stock',

        'maximum_stock',

    ];

    protected $casts = [

        'current_stock' => 'integer',

        'reserved_stock' => 'integer',

        'available_stock' => 'integer',

        'minimum_stock' => 'integer',

        'maximum_stock' => 'integer',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function movements()
{
    return $this->hasMany(
        StockMovement::class,
        'product_id',
        'product_id'
    );
}
}
