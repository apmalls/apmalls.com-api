<?php

namespace App\Services\Contracts;

use Illuminate\Support\Collection;

interface BarcodeGeneratorServiceInterface
{
    /**
     * Generate barcode.
     */
    public function generate(int $productId): array;

    /**
     * Generate bulk barcodes.
     */
    public function bulk(array $productIds): Collection;
}
