<?php

namespace App\Http\Controllers\Api\V1\Admin\Barcode;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Barcode\PrintBarcodeRequest;
use App\Services\Contracts\BarcodePrintServiceInterface;

class BarcodePrintController extends Controller
{
    public function __construct(
        protected BarcodePrintServiceInterface $service
    ) {
    }

    public function preview(
        PrintBarcodeRequest $request
    )
    {
        try {

            return $this->service->preview(

                $request->barcode_template_id,

                $request->products

            );

        } catch (Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ],500);

        }
    }

    public function pdf(
        PrintBarcodeRequest $request
    )
    {
        try {

            return $this->service->pdf(

                $request->barcode_template_id,

                $request->products

            );

        } catch (Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ],500);

        }
    }
}
