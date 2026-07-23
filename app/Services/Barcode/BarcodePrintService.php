<?php

namespace App\Services\Barcode;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\BarcodeTemplateRepositoryInterface;
use App\Services\Contracts\BarcodePrintServiceInterface;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BarcodePrintService implements BarcodePrintServiceInterface
{
    public function __construct(

        protected ProductRepositoryInterface $productRepository,

        protected BarcodeTemplateRepositoryInterface $templateRepository

    ) {
    }

    /**
     * Build printable items.
     */
    protected function buildItems(array $products): array
    {
        $ids = collect($products)

            ->pluck('product_id')

            ->unique()

            ->values()

            ->toArray();

        $records = $this->productRepository

            ->findManyByIds($ids);

        $items = [];

        foreach ($products as $row) {

            $product = $records->get($row['product_id']);

            if (!$product) {
                continue;
            }

            for ($i = 1; $i <= $row['quantity']; $i++) {

                $items[] = $product;

            }

        }

        return $items;
    }

    /**
     * Preview
     */
    public function preview(
        int $templateId,
        array $products
    ): Response {

        $template = $this->templateRepository
            ->findById($templateId);

        return response()->view(
            'barcode.preview',
            [
                'template' => $template,
                'items' => $this->buildItems($products)
            ]
        );
    }

    /**
     * PDF
     */
    public function pdf(
        int $templateId,
        array $products
    ): Response {

        $template = $this->templateRepository
            ->findById($templateId);

        return Pdf::loadView(

            'barcode.pdf',

            [
                'template' => $template,
                'items' => $this->buildItems($products)
            ]

        )->download('barcodes.pdf');
    }
}
