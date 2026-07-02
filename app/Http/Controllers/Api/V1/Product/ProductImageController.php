<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductImage\StoreProductImageRequest;
use App\Http\Requests\Product\ProductImage\UpdateProductImageRequest;
use App\Http\Requests\Product\ProductImage\UpdateProductImageSortOrderRequest;
use App\Models\Product;
use App\Models\Product\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class ProductImageController extends Controller
{
    /**
     * Product Gallery Listing
     */
    public function index($productId): JsonResponse
    {
        try {

            $product = Product::findOrFail($productId);

            $images = ProductImage::where(
                'product_id',
                $product->id
            )
                ->orderBy('sort_order')
                ->get();

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

            $product = Product::findOrFail($productId);

            $sortOrder = ProductImage::where(
                'product_id',
                $product->id
            )
                ->max('sort_order') ?? 0;

            foreach ($request->file('images') as $image) {

                $path = $this->uploadFile(

                    $image,

                    'products/gallery'

                );

                $uploadedImages[] = $path;

                ProductImage::create([

                    'product_id' => $product->id,

                    'image' => $path,

                    'sort_order' => ++$sortOrder,

                ]);

            }

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Product images uploaded successfully.',

                'data' => ProductImage::where(
                    'product_id',
                    $product->id
                )
                    ->orderBy('sort_order')
                    ->get(),

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            $this->cleanupUploadedFiles(
                $uploadedImages
            );

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

            $image = ProductImage::findOrFail($imageId);

            $newImage = $this->replaceFile(

                $request->file('image'),

                $image->image,

                'products/gallery'

            );

            $image->update([

                'image' => $newImage,

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Product image updated successfully.',

                'data' => $image->fresh(),

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

            $image = ProductImage::findOrFail($imageId);

            /*
            |--------------------------------------------------------------------------
            | Delete File
            |--------------------------------------------------------------------------
            */

            if (!empty($image->image)) {

                $this->deleteFile($image->image);

            }

            $image->delete();

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

            $image = ProductImage::findOrFail($imageId);

            $image->update([

                'sort_order' => $request->sort_order,

            ]);

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
