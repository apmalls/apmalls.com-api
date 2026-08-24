<?php

namespace App\Http\Controllers\Api\V1\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\Category\StoreCategoryRequest;
use App\Http\Requests\Product\Category\UpdateCategoryRequest;
use App\Models\Category\Category;
use App\Services\Contracts\CategoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryServiceInterface $categoryService
    ) {
    }

    /**
     * Category Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->filled('search') ? $request->search : null,
                'is_active' => $request->filled('status') ? $request->boolean('status') : null,
                'parent_id' => $request->filled('parent_id') ? $request->parent_id : null,
                'per_page' => $request->input('per_page', 10),
            ];

            $categories = $this->categoryService->paginate($filters);

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
        $this->beginTransaction();

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

            $category = $this->categoryService->create($data);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.',
                'data' => $category->load('parent')
            ], 201);

        } catch (ValidationException $e) {
            $this->rollback();
            $this->cleanupUploadedFile($data['image'] ?? null);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            $this->rollback();
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
            $category = $this->categoryService->find($id);

            return response()->json([
                'success' => true,
                'message' => 'Category fetched successfully.',
                'data' => $category->load(['parent', 'children'])
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
        $this->beginTransaction();

        try {
            $category = $this->categoryService->find($id);

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

            $category = $this->categoryService->update($id, $data);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data' => $category->load('parent')
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
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
     * Delete Category
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $category = $this->categoryService->find($id);

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
                $this->cleanupUploadedFile($category->image);
            }

            $this->categoryService->delete($id);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully.'
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);

        } catch (\Exception $e) {
            $this->rollback();
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

            $category = $this->categoryService->changeStatus($id, $request->boolean('is_active'));

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
            $filters = [
                'search' => $request->filled('search') ? $request->search : null,
                'per_page' => $request->input('per_page', 10),
            ];

            $categories = $this->categoryService->trash($filters);

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
        $this->beginTransaction();

        try {
            $category = $this->categoryService->find($id);

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

            $this->categoryService->restore($id);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Category restored successfully.',
                'data' => $category->load('parent')
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Category not found in trash.'
            ], 404);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Force Delete Category (Permanent)
     */
    public function forceDelete($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $category = $this->categoryService->find($id);

            // Check if category has children (including soft-deleted)
            if ($category->children()->withTrashed()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot permanently delete category with child categories.'
                ], 422);
            }

            // Delete Image
            if (!empty($category->image)) {
                $this->cleanupUploadedFile($category->image);
            }

            $this->categoryService->forceDelete($id);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Category permanently deleted successfully.'
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Get Category Tree (Nested)
     */
    public function tree(Request $request): JsonResponse
    {
        try {
            $categories = $this->categoryService->tree();

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

        $this->beginTransaction();

        try {
            $result = $this->categoryService->bulkDelete($request->ids);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => "{$result['deleted']} categories deleted successfully.",
                'data' => [
                    'deleted' => $result['deleted'],
                    'failed' => $result['failed']
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

}
