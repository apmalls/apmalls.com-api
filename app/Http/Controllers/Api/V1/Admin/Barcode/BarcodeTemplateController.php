<?php

namespace App\Http\Controllers\Api\V1\Admin\Barcode;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Barcode\BarcodeTemplateResource;
use App\Services\Contracts\BarcodeTemplateServiceInterface;
use App\Http\Requests\Admin\Barcode\StoreBarcodeTemplateRequest;
use App\Http\Requests\Admin\Barcode\UpdateBarcodeTemplateRequest;

class BarcodeTemplateController extends Controller
{
    public function __construct(
        protected BarcodeTemplateServiceInterface $service
    ) {
    }

    /**
     * Display listing.
     */
    public function index(): JsonResponse
    {
        try {

            $templates = $this->service->paginate(
                request()->integer('per_page', 10),
                request('search'),
                request('sort_by', 'id'),
                request('sort_order', 'desc')
            );

            return response()->json([
                'success' => true,
                'message' => 'Barcode templates fetched successfully.',
                'data' => BarcodeTemplateResource::collection($templates),
                'meta' => [
                    'current_page' => $templates->currentPage(),
                    'last_page' => $templates->lastPage(),
                    'per_page' => $templates->perPage(),
                    'total' => $templates->total(),
                ],
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch barcode templates.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Display single template.
     */
    public function show(int $id): JsonResponse
    {
        try {

            return response()->json([
                'success' => true,
                'message' => 'Barcode template fetched successfully.',
                'data' => new BarcodeTemplateResource(
                    $this->service->findById($id)
                ),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Barcode template not found.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Store template.
     */
    public function store(
        StoreBarcodeTemplateRequest $request
    ): JsonResponse {

        try {

            $template = $this->service->create(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Barcode template created successfully.',
                'data' => new BarcodeTemplateResource($template),
            ], 201);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to create barcode template.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Update template.
     */
    public function update(
        UpdateBarcodeTemplateRequest $request,
        int $id
    ): JsonResponse {

        try {

            $template = $this->service->update(
                $id,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Barcode template updated successfully.',
                'data' => new BarcodeTemplateResource($template),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update barcode template.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Delete template.
     */
    public function destroy(int $id): JsonResponse
    {
        try {

            $this->service->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Barcode template deleted successfully.',
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete barcode template.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Active templates.
     */
    public function active(): JsonResponse
    {
        try {

            return response()->json([
                'success' => true,
                'message' => 'Active barcode templates fetched successfully.',
                'data' => BarcodeTemplateResource::collection(
                    $this->service->active()
                ),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active barcode templates.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }
}
