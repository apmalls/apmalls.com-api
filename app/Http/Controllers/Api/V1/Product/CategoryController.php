<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreCategoryRequest;
use App\Http\Requests\Product\UpdateCategoryRequest;
use App\Models\Category;
use App\Traits\FileUploadTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    use FileUploadTrait;

    /**
     * Category Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::with(['parent', 'children'])
                ->latest();

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
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

            // Parent Category Filter
            if ($request->filled('parent_id')) {
                if ($request->parent_id === 'null' || $request->parent_id === '') {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $request->parent_id);
                }
            }

            $categories = $query->paginate(
                $request->get('per_page', 10)
            );

            return response()->json([
                'success' => true,
                'message' => 'Category list fetched successfully.',
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store Category
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'parent_id' => $request->parent_id,
                'name' => $request->name,
                'slug' => $request->slug ?: Str::slug($request->name),
                'description' => $request->description,
                'sort_order' => $request->sort_order ?? 0,
                'is_active' => $request->boolean('is_active'),
                'created_by' => auth()->id(),
            ];

            // Upload Image
            if ($request->hasFile('image')) {
                $data['image'] = $this->uploadFile(
                    $request->file('image'),
                    'categories'
                );
            }

            $category = Category::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.',
                'data' => $category->load('parent')
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            $this->cleanupUploadedFile($data['image'] ?? null);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->cleanupUploadedFile($data['image'] ?? null);

            return $this->handleException($e);
        }
    }

    /**
     * Display Category
     */
    public function show($id): JsonResponse
    {
        try {
            $category = Category::with(['parent', 'children'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Category fetched successfully.',
                'data' => $category
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update Category
     */
    public function update(UpdateCategoryRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $category = Category::findOrFail($id);

            // Prevent category from being its own parent
            if ($request->parent_id == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'A category cannot be its own parent.'
                ], 422);
            }

            $data = [
                'parent_id' => $request->parent_id,
                'name' => $request->name,
                'slug' => $request->slug ?: Str::slug($request->name),
                'description' => $request->description,
                'sort_order' => $request->sort_order ?? 0,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id(),
            ];

            // Replace Image
            if ($request->hasFile('image')) {
                $data['image'] = $this->replaceFile(
                    $request->file('image'),
                    $category->image,
                    'categories'
                );
            }

            $category->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data' => $category->fresh()->load('parent')
            ]);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Delete Category
     */
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $category = Category::findOrFail($id);

            // Check if category has children
            if ($category->children()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with child categories. Please delete or reassign child categories first.'
                ], 422);
            }

            // Check if category is being used by products
            if ($category->products()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category that has associated products. Please reassign products first.'
                ], 422);
            }

            // Delete Image
            if (!empty($category->image)) {
                $this->deleteFile($category->image);
            }

            $category->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully.'
            ]);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Change Category Status
     */
    public function changeStatus(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'is_active' => 'required|boolean'
            ]);

            $category = Category::findOrFail($id);

            $category->update([
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category status updated successfully.',
                'data' => $category
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
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
     * Get Trashed Categories
     */
    public function trash(Request $request): JsonResponse
    {
        try {
            $categories = Category::onlyTrashed()
                ->with(['parent', 'children'])
                ->latest()
                ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'message' => 'Trashed categories fetched successfully.',
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Restore Trashed Category
     */
    public function restore($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $category = Category::onlyTrashed()->findOrFail($id);

            // Check if parent category exists and is active
            if ($category->parent_id) {
                $parent = Category::where('id', $category->parent_id)->first();
                if (!$parent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parent category does not exist.'
                    ], 422);
                }
                if (!$parent->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parent category is inactive. Please activate parent category first.'
                    ], 422);
                }
            }

            $category->restore();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category restored successfully.',
                'data' => $category->load('parent')
            ]);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Category not found in trash.'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Force Delete Category (Permanent)
     */
    public function forceDelete($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $category = Category::withTrashed()->findOrFail($id);

            // Check if category has children (including soft-deleted)
            if ($category->children()->withTrashed()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot permanently delete category with child categories.'
                ], 422);
            }

            // Delete Image
            if (!empty($category->image)) {
                $this->deleteFile($category->image);
            }

            $category->forceDelete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category permanently deleted successfully.'
            ]);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Get Category Tree (Nested)
     */
    public function tree(Request $request): JsonResponse
    {
        try {
            $categories = Category::with([
                'children' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name');
                }
            ])
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Category tree fetched successfully.',
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Bulk Delete Categories
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:categories,id'
        ]);

        DB::beginTransaction();

        try {
            $categories = Category::whereIn('id', $request->ids)->get();
            $deleted = 0;
            $failed = [];

            foreach ($categories as $category) {
                if ($category->children()->exists() || $category->products()->exists()) {
                    $failed[] = $category->id;
                    continue;
                }

                if (!empty($category->image)) {
                    $this->deleteFile($category->image);
                }

                $category->delete();
                $deleted++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} categories deleted successfully.",
                'data' => [
                    'deleted' => $deleted,
                    'failed' => $failed
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Handle Exception
     */
    private function handleException(\Exception $e): JsonResponse
    {
        $statusCode = $e->getCode() >= 100 && $e->getCode() < 600 ? $e->getCode() : 500;

        // Log error in production
        if (config('app.env') !== 'local') {
            \Log::error('CategoryController Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => config('app.env') === 'local'
                ? $e->getMessage()
                : 'An error occurred while processing your request.',
            'code' => $statusCode
        ], $statusCode >= 400 && $statusCode < 600 ? $statusCode : 500);
    }

    /**
     * Cleanup Uploaded File
     */
    private function cleanupUploadedFile(?string $filePath): void
    {
        if (!empty($filePath)) {
            $this->deleteFile($filePath);
        }
    }
}
