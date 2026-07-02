<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\Brand\StoreBrandRequest;
use App\Http\Requests\Product\Brand\UpdateBrandRequest;
use App\Http\Requests\Product\Brand\ChangeBrandStatusRequest;
use App\Models\Product\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class BrandController extends Controller
{
    /**
     * Brand Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Brand::query()->latest();

            // Search
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('slug', 'ILIKE', "%{$search}%")
                        ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            }

            // Status Filter
            if ($request->filled('status')) {
                $query->where('is_active', $request->boolean('status'));
            }

            $brands = $query->paginate(
                $request->integer('per_page', 10)
            );

            return response()->json([
                'success' => true,
                'message' => 'Brand list fetched successfully.',
                'data' => $brands,
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store Brand
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $this->beginTransaction();

        try {
            $data = [
                'name' => $request->name,
                'slug' => $request->slug ?: Str::slug($request->name),
                'description' => $request->description,
                'is_active' => $request->boolean('is_active'),
                'created_by' => auth()->id(),
            ];

            // Upload Logo
            if ($request->hasFile('logo')) {
                $data['logo'] = $this->uploadFile(
                    $request->file('logo'),
                    'brands'
                );
            }

            $brand = Brand::create($data);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Brand created successfully.',
                'data' => $brand,
            ], 201);

        } catch (ValidationException $e) {
            $this->rollback();
            $this->cleanupUploadedFile($data['logo'] ?? null);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            $this->rollback();
            $this->cleanupUploadedFile($data['logo'] ?? null);

            return $this->handleException($e);
        }
    }

    /**
     * Display Brand
     */
    public function show($id): JsonResponse
    {
        try {
            $brand = Brand::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Brand fetched successfully.',
                'data' => $brand,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.',
            ], 404);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update Brand
     */
    public function update(UpdateBrandRequest $request, $id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $brand = Brand::findOrFail($id);

            $data = [
                'name' => $request->name,
                'slug' => $request->slug ?: Str::slug($request->name),
                'description' => $request->description,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id(),
            ];

            // Replace Logo
            if ($request->hasFile('logo')) {
                $data['logo'] = $this->replaceFile(
                    $request->file('logo'),
                    $brand->logo,
                    'brands'
                );
            }

            $brand->update($data);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully.',
                'data' => $brand->fresh(),
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.'
            ], 404);

        } catch (ValidationException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Delete Brand (Soft Delete)
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $brand = Brand::findOrFail($id);

            // Check if brand is being used by products
            if ($brand->products()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete brand that has associated products. Please reassign products first.'
                ], 422);
            }

            // Delete Logo
            if (!empty($brand->logo)) {
                $this->cleanupUploadedFile($brand->logo);
            }

            $brand->delete();

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Brand deleted successfully.'
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.'
            ], 404);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Change Brand Status
     */
    public function changeStatus(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'is_active' => 'required|boolean'
            ]);

            $brand = Brand::findOrFail($id);

            $brand->update([
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Brand status updated successfully.',
                'data' => $brand
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.'
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get Trashed Brands
     */
    public function trash(Request $request): JsonResponse
    {
        try {
            $brands = Brand::onlyTrashed()
                ->latest()
                ->paginate($request->integer('per_page', 10));

            return response()->json([
                'success' => true,
                'message' => 'Trashed brands fetched successfully.',
                'data' => $brands
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Restore Trashed Brand
     */
    public function restore($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $brand = Brand::onlyTrashed()->findOrFail($id);
            $brand->restore();

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Brand restored successfully.',
                'data' => $brand
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Brand not found in trash.'
            ], 404);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Force Delete Brand (Permanent)
     */
    public function forceDelete($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $brand = Brand::withTrashed()->findOrFail($id);

            // Check if brand is being used by products (including soft-deleted)
            if ($brand->products()->withTrashed()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot permanently delete brand that has associated products.'
                ], 422);
            }

            // Delete Logo
            if (!empty($brand->logo)) {
                $this->cleanupUploadedFile($brand->logo);
            }

            $brand->forceDelete();

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Brand permanently deleted successfully.'
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.'
            ], 404);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Bulk Delete Brands
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:brands,id'
        ]);

        $this->beginTransaction();

        try {
            $brands = Brand::whereIn('id', $request->ids)->get();
            $deleted = 0;
            $failed = [];

            foreach ($brands as $brand) {
                // Check if brand has products
                if ($brand->products()->exists()) {
                    $failed[] = [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'reason' => 'Has associated products'
                    ];
                    continue;
                }

                // Delete Logo
                if (!empty($brand->logo)) {
                    $this->cleanupUploadedFile($brand->logo);
                }

                $brand->delete();
                $deleted++;
            }

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} brands deleted successfully.",
                'data' => [
                    'deleted' => $deleted,
                    'failed' => $failed
                ]
            ]);

        } catch (ValidationException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Get Active Brands List (for dropdown)
     */
    public function dropdown(Request $request): JsonResponse
    {
        try {
            $brands = Brand::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'logo']);

            return response()->json([
                'success' => true,
                'message' => 'Brands fetched successfully.',
                'data' => $brands
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Bulk Update Brand Status
     */
    public function bulkStatusUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:brands,id',
            'is_active' => 'required|boolean'
        ]);

        try {
            Brand::whereIn('id', $request->ids)
                ->update([
                    'is_active' => $request->boolean('is_active'),
                    'updated_by' => auth()->id(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Brand status updated successfully.',
                'data' => [
                    'updated_count' => count($request->ids),
                    'is_active' => $request->boolean('is_active')
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

}
