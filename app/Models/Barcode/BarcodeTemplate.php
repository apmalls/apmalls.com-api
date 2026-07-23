<?php

namespace App\Models\Barcode;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BarcodeTemplate extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'paper_size',
        'width',
        'height',
        'font_size',
        'show_name',
        'show_price',
        'show_sku',
        'show_barcode',
        'show_qr',
        'status',
    ];

}
