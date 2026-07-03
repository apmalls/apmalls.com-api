<?php

namespace App\Models\Inventory;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockAdjustment extends Model
{
     use HasFactory, SoftDeletes;

    protected $fillable = [

        'product_id',

        'system_stock',

        'physical_stock',

        'difference',

        'reason',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'system_stock' => 'integer',

        'physical_stock' => 'integer',

        'difference' => 'integer',

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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
