<?php

declare(strict_types=1);

namespace App\Repositories\Category;

use App\Models\Category\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    /**
     * Get paginated categories.
     */
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        return Category::query()

            ->with([
                'parent',
                'children',
                'creator',
                'updater',
            ])

            ->withCount('products')

            ->when(

                !empty($filters['search']),

                function ($query) use ($filters) {

                    $query->where(function ($query) use ($filters) {

                        $query->where(
                            'name',
                            'ILIKE',
                            '%' . trim($filters['search']) . '%'
                        )

                            ->orWhere(
                                'slug',
                                'ILIKE',
                                '%' . trim($filters['search']) . '%'
                            );

                    });

                }

            )

            ->when(

                isset($filters['is_active']),

                fn($query) => $query->where(
                    'is_active',
                    $filters['is_active']
                )

            )

            ->when(

                isset($filters['parent_id']),

                fn($query) => $query->where(
                    'parent_id',
                    $filters['parent_id']
                )

            )

            ->orderBy('sort_order')

            ->paginate(
                $filters['per_page'] ?? 10
            );

    }

    /**
     * Trash Listing
     */
    public function trash(
        array $filters = []
    ): LengthAwarePaginator {

        return Category::onlyTrashed()

            ->with([
                'parent',
                'creator',
                'updater',
            ])

            ->when(

                !empty($filters['search']),

                fn($query) =>

                $query->where(
                    'name',
                    'ILIKE',
                    '%' . trim($filters['search']) . '%'
                )

            )

            ->latest('deleted_at')

            ->paginate(
                $filters['per_page'] ?? 10
            );

    }

    /**
     * Get All Categories
     */
    public function all(): Collection
    {
        return Category::query()

            ->with('parent')

            ->orderBy('sort_order')

            ->get();

    }

    /**
     * Find Category
     */
    public function find(
        int $id
    ): Category {

        return Category::query()

            ->with([
                'parent',
                'children',
                'products',
                'creator',
                'updater',
            ])

            ->findOrFail($id);

    }

    /**
     * Create Category
     */
    public function create(
        array $data
    ): Category {

        return Category::create($data);

    }

    /**
     * Update Category
     */
    public function update(
        int $id,
        array $data
    ): Category {

        $category = $this->find($id);

        $category->update($data);

        return $category->refresh();

    }

    /**
     * Delete Category
     */
    public function delete(
        int $id
    ): bool {

        return (bool) $this->find($id)
            ->delete();

    }

    /**
     * Restore Category
     */
    public function restore(
        int $id
    ): bool {

        return (bool) Category::onlyTrashed()

            ->findOrFail($id)

            ->restore();

    }

    /**
     * Force Delete Category
     */
    public function forceDelete(
        int $id
    ): bool {

        return (bool) Category::onlyTrashed()

            ->findOrFail($id)

            ->forceDelete();

    }

    /**
     * Category Dropdown
     */
    public function dropdown(): Collection
    {
        return Category::query()

            ->select([
                'id',
                'name',
            ])

            ->where(
                'is_active',
                true
            )

            ->orderBy('sort_order')

            ->get();

    }

    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    /**
     * Website Category Listing
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator {

        return Category::query()

            ->with([
                'children' => fn($query) =>

                    $query

                        ->where('is_active', true)

                        ->withCount([
                            'products' => fn($query) =>
                                $query->where(
                                    'is_active',
                                    true
                                ),
                        ])

                        ->orderBy('sort_order'),
            ])

            ->where(
                'is_active',
                true
            )

            ->whereNull('parent_id')

            ->when(

                !empty($filters['search']),

                fn($query) =>

                $query->where(
                    'name',
                    'ILIKE',
                    '%' . trim($filters['search']) . '%'
                )

            )

            ->orderBy('sort_order')

            ->paginate(
                $filters['per_page'] ?? 20
            );

    }

    /**
     * Featured Categories
     */
    public function featured(): Collection
    {
        return Category::query()

            ->with([
                'children' => fn($query) =>

                    $query

                        ->where('is_active', true)

                        ->withCount([
                            'products' => fn($query) =>
                                $query->where(
                                    'is_active',
                                    true
                                ),
                        ])

                        ->orderBy('sort_order'),
            ])

            ->where(
                'is_active',
                true
            )

            ->where(
                'is_featured',
                true
            )

            ->whereNull('parent_id')

            ->orderBy('sort_order')

            ->get();

    }

    /**
     * Find Category By Slug
     */
    public function findBySlug(
        string $slug
    ): Category {

        return Category::query()

            ->with([

                'parent',

                'children' => fn($query) =>

                    $query

                        ->where('is_active', true)

                        ->withCount([
                            'products' => fn($query) =>
                                $query->where(
                                    'is_active',
                                    true
                                ),
                        ])

                        ->orderBy('sort_order'),

            ])

            ->where(
                'slug',
                $slug
            )

            ->where(
                'is_active',
                true
            )

            ->firstOrFail();

    }
}
