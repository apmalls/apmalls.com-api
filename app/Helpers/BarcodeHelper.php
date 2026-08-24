<?php

namespace App\Helpers;

use Milon\Barcode\Facades\DNS1DFacade as DNS1D;

class BarcodeHelper
{
    /**
     * SVG Barcode
     */
    public static function svg(
        string $barcode,
        string $type = 'CODE128'
    ): string {

        return DNS1D::getBarcodeSVG(
            $barcode,
            $type
        );
    }

    /**
     * PNG Barcode
     */
    public static function png(
        string $barcode,
        string $type = 'CODE128'
    ): string {

        return DNS1D::getBarcodePNG(
            $barcode,
            $type
        );
    }

    /**
     * HTML Barcode
     */
    public static function html(
        string $barcode,
        string $type = 'C128'
    ): string {

        return DNS1D::getBarcodeHTML(
            $barcode,
            $type
        );
    }
}
