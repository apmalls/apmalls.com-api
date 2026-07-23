<?php

namespace App\Http\Controllers\Api\V1\Admin\Barcode;

use Illuminate\Http\Request;
use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Barcode\ProductBarcodeResource;
use App\Services\Contracts\BarcodeGeneratorServiceInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;

class ProductBarcodeController extends Controller
{
    public function __construct(
        protected BarcodeGeneratorServiceInterface $service
    ) {
    }

    /**
     * Generate barcode.
     */
    public function show(int $id): JsonResponse
    {
        try {

            return response()->json([
                'success' => true,
                'message' => 'Barcode generated successfully.',
                'data' => new ProductBarcodeResource(
                    $this->service->generate($id)
                ),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate barcode.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Generate bulk barcodes.
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        try {

            return response()->json([
                'success' => true,
                'message' => 'Barcodes generated successfully.',
                'data' => ProductBarcodeResource::collection(
                    $this->service->bulk(
                        $request->product_ids
                    )
                ),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate barcodes.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }
}
