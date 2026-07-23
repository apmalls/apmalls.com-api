<?php

namespace App\Services\Barcode;
use Illuminate\Support\Collection;

use App\Helpers\BarcodeHelper;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\BarcodeGeneratorServiceInterface;

class BarcodeGeneratorService implements BarcodeGeneratorServiceInterface
{
   public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Generate single barcode.
     */
    public function generate(int $productId): array
    {
        $product = $this->productRepository->findById($productId);

        return [

            'product_id'   => $product->id,
            'name'         => $product->name,
            'sku'          => $product->sku,
            'barcode'      => $product->barcode,
            'barcode_type' => $product->barcode_type,

            'svg' => BarcodeHelper::svg(
                $product->barcode,
                $product->barcode_type
            ),

            'png' => BarcodeHelper::png(
                $product->barcode,
                $product->barcode_type
            ),

            'html' => BarcodeHelper::html(
                $product->barcode,
                $product->barcode_type
            ),
        ];
    }

    /**
     * Generate bulk barcodes.
     */
    public function bulk(array $productIds): Collection
    {
        return $this->productRepository
            ->findMany($productIds)
            ->map(function ($product) {

                return $this->generate($product->id);

            });
    }
}
