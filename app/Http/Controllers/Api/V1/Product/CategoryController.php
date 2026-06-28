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

class CategoryController extends Controller
{
    use FileUploadTrait;

    /**
     * Category Listing
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::with(['parent', 'children'])
            ->latest();

        if ($request->filled('search')) {

            $query->where('name', 'ILIKE', "%{$request->search}%");

        }

        if ($request->filled('status')) {

            $query->where('is_active', $request->status);

        }

        if ($request->filled('parent_id')) {

            $query->where('parent_id', $request->parent_id);

        }

        $categories = $query->paginate(
            $request->get('per_page', 10)
        );

        return response()->json([

            'success' => true,

            'message' => 'Category list fetched successfully.',

            'data' => $categories

        ]);
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

            /**
             * Upload Image
             */
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

        } catch (\Throwable $e) {

            DB::rollBack();

            if (!empty($data['image'])) {

                $this->deleteFile($data['image']);

            }

            return response()->json([

                'success' => false,

                'message' => $e->getMessage()

            ], 500);

        }
    }

    /**
     * Display Category
     */
    public function show(Category $category): JsonResponse
    {
        $category->load(['parent', 'children']);

        return response()->json([

            'success' => true,

            'message' => 'Category fetched successfully.',

            'data' => $category

        ]);
    }

    /**
     * Update Category
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
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

                'updated_by' => auth()->id(),

            ];

            /**
             * Replace Image
             */
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

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => $e->getMessage()

            ], 500);

        }
    }

    /**
     * Delete Category
     */
    public function destroy(Category $category): JsonResponse
    {
        DB::beginTransaction();

        try {

            /**
             * Check Child Category
             */
            if ($category->children()->exists()) {

                return response()->json([

                    'success' => false,

                    'message' => 'Child categories exist. Delete them first.'

                ], 422);

            }

            /**
             * Delete Image
             */
            if (!empty($category->image)) {

                $this->deleteFile($category->image);

            }

            $category->delete();

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Category deleted successfully.'

            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => $e->getMessage()

            ], 500);

        }
    }


    /**
     * Change Category Status
     */
    public function changeStatus(Request $request, Category $category): JsonResponse
    {
        $request->validate([

            'is_active' => 'required|boolean'

        ]);

        $category->update([

            'is_active' => $request->boolean('is_active'),

            'updated_by' => auth()->id(),

        ]);

        return response()->json([

            'success' => true,

            'message' => 'Category status updated successfully.',

            'data' => $category

        ]);
    }


}
