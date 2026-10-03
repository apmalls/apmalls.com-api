<?php

namespace App\Models\Barcode;

use Illuminate\Database\Eloquent\Model;

class BarcodeTemplate extends Model
{
    protected $fillable = [
        'name',
        'paper_size',
        'width',
        'height',
        'font_size',
        'show_name',
        'show_price',
        'show_sku',
        'show_manufacture_date',
        'show_expiry_date',
        'show_barcode',
        'show_qr',
        'status',
    ];

    protected $casts = [
        'show_name' => 'boolean',
        'show_price' => 'boolean',
        'show_sku' => 'boolean',
        'show_manufacture_date' => 'boolean',
        'show_expiry_date' => 'boolean',
        'show_barcode' => 'boolean',
        'show_qr' => 'boolean',
        'status' => 'boolean',
    ];

    public static function sizeKey(int $width, int $height): string
    {
        return $width.'x'.$height;
    }

}
