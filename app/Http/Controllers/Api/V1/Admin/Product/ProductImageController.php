<?php

namespace App\Http\Controllers\Api\V1\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductImage\StoreProductImageRequest;
use App\Http\Requests\Product\ProductImage\UpdateProductImageRequest;
use App\Http\Requests\Product\ProductImage\UpdateProductImageSortOrderRequest;
use App\Services\Contracts\ProductImageServiceInterface;
use App\Models\Product\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function __construct(
        protected ProductImageServiceInterface $productImageService
    ) {
    }

    /**
     * Product Gallery Listing
     */
    public function index($productId): JsonResponse
    {
        try {
            $images = $this->productImageService->getByProduct($productId);

            return response()->json([

                'success' => true,

                'message' => 'Product images fetched successfully.',

                'data' => $images,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Upload Product Images
     */
    public function store(
        StoreProductImageRequest $request,
        $productId
    ): JsonResponse {

        $this->beginTransaction();

        $uploadedImages = [];

        try {
            Product::findOrFail($productId);

            $sortOrder = $this->productImageService->getByProduct($productId)->max('sort_order') ?? 0;

            foreach ($request->file('images') as $image) {

                $path = $this->uploadFile(
                    $image,
                    'products/gallery'
                );

                $uploadedImages[] = $path;

                $this->productImageService->create([
                    'product_id' => $productId,
                    'image' => $path,
                    'sort_order' => ++$sortOrder,
                ]);

            }

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Product images uploaded successfully.',

                'data' => $this->productImageService->getByProduct($productId),

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            $this->cleanupUploadedFiles($uploadedImages);

            return $this->handleException($e);

        }

    }

    /**
     * Replace Product Image
     */
    public function update(
        UpdateProductImageRequest $request,
        $imageId
    ): JsonResponse {

        $this->beginTransaction();

        $newImage = null;

        try {
            $image = $this->productImageService->find($imageId);

            $newImage = $this->replaceFile(
                $request->file('image'),
                $image->image,
                'products/gallery'
            );

            $image = $this->productImageService->update($imageId, [
                'image' => $newImage,
            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Product image updated successfully.',

                'data' => $image,

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            $this->cleanupUploadedFile($newImage);

            return $this->handleException($e);

        }

    }

    /**
     * Delete Product Image
     */
    public function destroy($imageId): JsonResponse
    {
        $this->beginTransaction();

        try {
            $image = $this->productImageService->find($imageId);

            if (!empty($image->image)) {
                $this->deleteFile($image->image);
            }

            $this->productImageService->delete($imageId);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Product image deleted successfully.',

            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Update Image Sort Order
     */
    public function updateSortOrder(
        UpdateProductImageSortOrderRequest $request,
        $imageId
    ): JsonResponse {

        try {
            $image = $this->productImageService->updateSortOrder($imageId, $request->sort_order);

            return response()->json([

                'success' => true,

                'message' => 'Image sort order updated successfully.',

                'data' => $image,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }

    }

}
