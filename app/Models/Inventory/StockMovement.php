<?php

namespace App\Models\Inventory;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [

        'product_id',

        'reference_type',

        'reference_id',

        'movement_type',

        'quantity',

        'stock_before',

        'stock_after',

        'remarks',

        'created_by',

    ];

    protected $casts = [

        'quantity' => 'integer',

        'stock_before' => 'integer',

        'stock_after' => 'integer',

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
}
