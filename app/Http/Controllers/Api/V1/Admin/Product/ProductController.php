<?php

namespace App\Http\Controllers\Api\V1\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\ChangeProductStatusRequest;
use App\Services\Contracts\ProductServiceInterface;
use App\Models\Product\Product;
use App\Models\Product\ProductImage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(
        protected ProductServiceInterface $productService
    ) {
    }

    /**
     * Product Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->filled('search') ? $request->search : null,
                'category_id' => $request->filled('category_id') ? $request->category_id : null,
                'brand_id' => $request->filled('brand_id') ? $request->brand_id : null,
                'unit_id' => $request->filled('unit_id') ? $request->unit_id : null,
                'is_active' => $request->filled('status') ? $request->boolean('status') : null,
                'featured' => $request->filled('featured') ? $request->boolean('featured') : null,
                'new_arrival' => $request->filled('new_arrival') ? $request->boolean('new_arrival') : null,
                'best_seller' => $request->filled('best_seller') ? $request->boolean('best_seller') : null,
                'min_price' => $request->filled('min_price') ? $request->min_price : null,
                'max_price' => $request->filled('max_price') ? $request->max_price : null,
                'per_page' => $request->integer('per_page', 10),
            ];

            $products = $this->productService->paginate($filters);

            return response()->json([
                'success' => true,
                'message' => 'Products fetched successfully.',
                'data' => $products,
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store Product
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->beginTransaction();

        $thumbnail = null;
        $uploadedImages = [];

        try {
            $data = [
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'unit_id' => $request->unit_id,
                'name' => $request->name,
                'slug' => $request->filled('slug')
                    ? Str::slug($request->slug)
                    : Str::slug($request->name),
                'sku' => $this->productService->generateSku(),
                'barcode' => $request->barcode,
                'hsn_code' => $request->hsn_code,
                'short_description' => $request->short_description,
                'description' => $request->description,
                'purchase_price' => $request->purchase_price,
                'selling_price' => $request->selling_price,
                'mrp' => $request->mrp,
                'tax_percent' => $request->tax_percent,
                'discount_percent' => $request->discount_percent,
                'stock' => $request->stock,
                'minimum_stock' => $request->minimum_stock,
                'featured' => $request->boolean('featured'),
                'new_arrival' => $request->boolean('new_arrival'),
                'best_seller' => $request->boolean('best_seller'),
                'is_active' => $request->boolean('is_active'),
                'created_by' => auth()->id(),
            ];

            if ($request->hasFile('thumbnail')) {
                $thumbnail = $this->uploadFile(
                    $request->file('thumbnail'),
                    'products/thumbnail'
                );
                $data['thumbnail'] = $thumbnail;
            }

            $product = $this->productService->create($data);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $this->uploadFile($image, 'products/gallery');
                    $uploadedImages[] = $path;

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $path,
                        'sort_order' => $index + 1,
                    ]);
                }
            }

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'data' => $product->load([
                    'category',
                    'brand',
                    'unit',
                    'images',
                ])
            ], 201);

        } catch (\Exception $e) {
            $this->rollback();
            $this->cleanupUploadedFile($thumbnail);
            $this->cleanupUploadedFiles($uploadedImages);
            return $this->handleException($e);
        }
    }

    /**
     * Display Product
     */
    public function show($id): JsonResponse
    {
        try {
            $product = $this->productService->find($id);

            return response()->json([
                'success' => true,
                'message' => 'Product fetched successfully.',
                'data' => $product->load([
                    'category',
                    'brand',
                    'unit',
                    'images',
                    'creator',
                    'updater',
                ]),
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update Product
     */
    public function update(UpdateProductRequest $request, $id): JsonResponse
    {
        $this->beginTransaction();

        $newThumbnail = null;
        $uploadedImages = [];

        try {
            $product = $this->productService->find($id);

            $data = [
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'unit_id' => $request->unit_id,
                'name' => $request->name,
                'slug' => $request->filled('slug')
                    ? Str::slug($request->slug)
                    : Str::slug($request->name),
                'sku' => $product->sku,
                'barcode' => $request->filled('barcode')
                    ? $request->barcode
                    : $product->barcode,
                'hsn_code' => $request->hsn_code,
                'short_description' => $request->short_description,
                'description' => $request->description,
                'purchase_price' => $request->purchase_price,
                'selling_price' => $request->selling_price,
                'mrp' => $request->mrp,
                'tax_percent' => $request->tax_percent,
                'discount_percent' => $request->discount_percent,
                'stock' => $request->stock,
                'minimum_stock' => $request->minimum_stock,
                'featured' => $request->boolean('featured'),
                'new_arrival' => $request->boolean('new_arrival'),
                'best_seller' => $request->boolean('best_seller'),
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id(),
            ];

            if ($request->hasFile('thumbnail')) {
                $newThumbnail = $this->replaceFile(
                    $request->file('thumbnail'),
                    $product->thumbnail,
                    'products/thumbnail'
                );
                $data['thumbnail'] = $newThumbnail;
            }

            $product = $this->productService->update($id, $data);

            if ($request->hasFile('images')) {
                $sortOrder = $product->images()->max('sort_order') ?? 0;

                foreach ($request->file('images') as $image) {
                    $path = $this->uploadFile($image, 'products/gallery');
                    $uploadedImages[] = $path;

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $path,
                        'sort_order' => ++$sortOrder,
                    ]);
                }
            }

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.',
                'data' => $product->fresh()->load([
                    'category',
                    'brand',
                    'unit',
                    'images',
                ])
            ]);

        } catch (\Exception $e) {
            $this->rollback();
            $this->cleanupUploadedFile($newThumbnail);
            $this->cleanupUploadedFiles($uploadedImages);
            return $this->handleException($e);
        }
    }

    /**
     * Soft Delete Product
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $product = $this->productService->find($id);

            if (!empty($product->thumbnail)) {
                $this->deleteFile($product->thumbnail);
            }

            foreach ($product->images as $image) {
                $this->deleteFile($image->image);
            }

            $this->productService->delete($id);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.',
            ]);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Change Product Status
     */
    public function changeStatus(ChangeProductStatusRequest $request, $id): JsonResponse
    {
        try {
            $product = $this->productService->changeStatus($id, $request->boolean('is_active'));

            return response()->json([
                'success' => true,
                'message' => 'Product status updated successfully.',
                'data' => $product,
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Trash Products
     */
    public function trash(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->filled('search') ? $request->search : null,
                'per_page' => $request->integer('per_page', 10),
            ];

            $products = $this->productService->trash($filters);

            return response()->json([
                'success' => true,
                'message' => 'Deleted products fetched successfully.',
                'data' => $products,
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Restore Product
     */
    public function restore($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $this->productService->restore($id);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Product restored successfully.',
            ]);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Permanently Delete Product
     */
    public function forceDelete($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $product = $this->productService->find($id);

            if (!empty($product->thumbnail)) {
                $this->deleteFile($product->thumbnail);
            }

            foreach ($product->images as $image) {
                $this->deleteFile($image->image);
            }

            $this->productService->forceDelete($id);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Product permanently deleted successfully.',
            ]);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }
}
