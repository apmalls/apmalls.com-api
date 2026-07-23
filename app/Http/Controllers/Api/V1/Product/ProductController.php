<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\ChangeProductStatusRequest;


use App\Models\Product\Product;
use App\Models\Product\ProductImage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Product Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $query = Product::query()
                ->with([
                    'category',
                    'brand',
                    'unit',
                    'images',
                ])
                ->latest();

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('sku', 'ILIKE', "%{$search}%")
                        ->orWhere('barcode', 'ILIKE', "%{$search}%")
                        ->orWhere('slug', 'ILIKE', "%{$search}%")
                        ->orWhere('hsn_code', 'ILIKE', "%{$search}%");

                });

            }

            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            if ($request->filled('category_id')) {

                $query->where(
                    'category_id',
                    $request->category_id
                );

            }

            if ($request->filled('brand_id')) {

                $query->where(
                    'brand_id',
                    $request->brand_id
                );

            }

            if ($request->filled('unit_id')) {

                $query->where(
                    'unit_id',
                    $request->unit_id
                );

            }

            if ($request->filled('status')) {

                $query->where(
                    'is_active',
                    $request->boolean('status')
                );

            }

            if ($request->filled('featured')) {

                $query->where(
                    'featured',
                    $request->boolean('featured')
                );

            }

            if ($request->filled('new_arrival')) {

                $query->where(
                    'new_arrival',
                    $request->boolean('new_arrival')
                );

            }

            if ($request->filled('best_seller')) {

                $query->where(
                    'best_seller',
                    $request->boolean('best_seller')
                );

            }

            /*
            |--------------------------------------------------------------------------
            | Price Filter
            |--------------------------------------------------------------------------
            */

            if ($request->filled('min_price')) {

                $query->where(
                    'selling_price',
                    '>=',
                    $request->min_price
                );

            }

            if ($request->filled('max_price')) {

                $query->where(
                    'selling_price',
                    '<=',
                    $request->max_price
                );

            }

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            $products = $query->paginate(
                $request->integer('per_page', 10)
            );

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

                /*
                |--------------------------------------------------------------------------
                | Auto SKU
                |--------------------------------------------------------------------------
                */

                'sku' => $this->generateSku(),

                /*
                |--------------------------------------------------------------------------
                | Barcode
                |--------------------------------------------------------------------------
                | Manual barcode accepted.
                | If empty then system generated.
                */

                'barcode' => $request->filled('barcode')
                    ? $request->barcode
                    : $this->generateBarcode(),

                // if (blank($data['barcode'])) {

                //     $setting = $this->generalSettingRepository->get();

                //     $data['barcode'] =
                //         $setting->barcode_prefix .
                //         $setting->barcode_start_number;

                //     $setting->increment('barcode_start_number');
                // }

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

            /*
            |--------------------------------------------------------------------------
            | Upload Thumbnail
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('thumbnail')) {

                $thumbnail = $this->uploadFile(

                    $request->file('thumbnail'),

                    'products/thumbnail'

                );

                $data['thumbnail'] = $thumbnail;

            }

            /*
            |--------------------------------------------------------------------------
            | Create Product
            |--------------------------------------------------------------------------
            */

            $product = Product::create($data);

            /*
            |--------------------------------------------------------------------------
            | Upload Gallery Images
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('images')) {

                foreach ($request->file('images') as $index => $image) {

                    $path = $this->uploadFile(

                        $image,

                        'products/gallery'

                    );

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

            /*
            |--------------------------------------------------------------------------
            | Delete Uploaded Files
            |--------------------------------------------------------------------------
            */

            $this->cleanupUploadedFile($thumbnail);

            $this->cleanupUploadedFiles($uploadedImages);

            return $this->handleException($e);

        }
    }

    /**
     * Generate SKU
     */
    private function generateSku(): string
    {
        do {

            $nextId = (Product::max('id') ?? 0) + 1;

            $sku = 'SKU-' . str_pad(
                $nextId,
                6,
                '0',
                STR_PAD_LEFT
            );

        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Generate Barcode
     */
    private function generateBarcode(): string
    {
        do {

            $nextId = (Product::max('id') ?? 0) + 1;

            $barcode = '890' . str_pad(
                $nextId,
                9,
                '0',
                STR_PAD_LEFT
            );

        } while (Product::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Display Product
     */
    public function show($id): JsonResponse
    {
        try {

            $product = Product::with([

                'category',

                'brand',

                'unit',

                'images',

                'creator',

                'updater',

            ])->findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Product fetched successfully.',

                'data' => $product,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Update Product
     */
    public function update(
        UpdateProductRequest $request,
        $id
    ): JsonResponse {

        $this->beginTransaction();

        $newThumbnail = null;

        $uploadedImages = [];

        try {

            $product = Product::with('images')
                ->findOrFail($id);

            $data = [

                'category_id' => $request->category_id,

                'brand_id' => $request->brand_id,

                'unit_id' => $request->unit_id,

                'name' => $request->name,

                'slug' => $request->filled('slug')
                    ? Str::slug($request->slug)
                    : Str::slug($request->name),

                /*
                |--------------------------------------------------------------------------
                | SKU Never Changes
                |--------------------------------------------------------------------------
                */

                'sku' => $product->sku,

                /*
                |--------------------------------------------------------------------------
                | Barcode
                |--------------------------------------------------------------------------
                */

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

            /*
    |--------------------------------------------------------------------------
    | Replace Thumbnail
    |--------------------------------------------------------------------------
    */

            if ($request->hasFile('thumbnail')) {

                $newThumbnail = $this->replaceFile(

                    $request->file('thumbnail'),

                    $product->thumbnail,

                    'products/thumbnail'

                );

                $data['thumbnail'] = $newThumbnail;

            }

            /*
            |--------------------------------------------------------------------------
            | Update Product
            |--------------------------------------------------------------------------
            */

            $product->update($data);

            /*
    |--------------------------------------------------------------------------
    | Upload Gallery Images
    |--------------------------------------------------------------------------
    */

            if ($request->hasFile('images')) {

                $sortOrder = $product->images()
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

            /*
            |--------------------------------------------------------------------------
            | Cleanup Files
            |--------------------------------------------------------------------------
            */

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

            $product = Product::with('images')->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Delete Thumbnail
            |--------------------------------------------------------------------------
            */

            if (!empty($product->thumbnail)) {

                $this->deleteFile($product->thumbnail);

            }

            /*
            |--------------------------------------------------------------------------
            | Delete Gallery Images
            |--------------------------------------------------------------------------
            */

            foreach ($product->images as $image) {

                $this->deleteFile($image->image);

            }

            $product->delete();

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
    public function changeStatus(
        ChangeProductStatusRequest $request,
        $id
    ): JsonResponse {

        try {

            $product = Product::findOrFail($id);

            $product->update([

                'is_active' => $request->boolean('is_active'),

                'updated_by' => auth()->id(),

            ]);

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

            $query = Product::onlyTrashed()
                ->with([
                    'category',
                    'brand',
                    'unit',
                ])
                ->latest('deleted_at');

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('sku', 'ILIKE', "%{$search}%")
                        ->orWhere('barcode', 'ILIKE', "%{$search}%");

                });

            }

            return response()->json([

                'success' => true,

                'message' => 'Deleted products fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

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

            $product = Product::onlyTrashed()
                ->findOrFail($id);

            $product->restore();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Product restored successfully.',

                'data' => $product,

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

            $product = Product::onlyTrashed()
                ->with('images')
                ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Delete Thumbnail
            |--------------------------------------------------------------------------
            */

            if (!empty($product->thumbnail)) {

                $this->deleteFile($product->thumbnail);

            }

            /*
            |--------------------------------------------------------------------------
            | Delete Gallery Images
            |--------------------------------------------------------------------------
            */

            foreach ($product->images as $image) {

                $this->deleteFile($image->image);

            }

            $product->images()->delete();

            $product->forceDelete();

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
