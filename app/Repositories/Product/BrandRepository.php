<?php

declare(strict_types=1);

namespace App\Repositories\Product;

use App\Models\Product\Brand;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\BrandRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandRepository implements BrandRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    /**
     * Get paginated brands.
     */
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        return Brand::query()

            ->with([
                'creator',
                'updater',
            ])

            ->withCount('products')

            ->when(

                !empty($filters['search']),

                function ($query) use ($filters) {

                    $query->where(function ($query) use ($filters) {

                        $query

                            ->where(
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

                fn($query) =>

                $query->where(
                    'is_active',
                    $filters['is_active']
                )

            )

            ->latest()

            ->paginate(
                $filters['per_page'] ?? 10
            );

    }

    /**
     * Get trashed brands.
     */
    public function trash(
        array $filters = []
    ): LengthAwarePaginator {

        return Brand::onlyTrashed()

            ->with([
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
     * Get all brands.
     */
    public function all(): Collection
    {
        return Brand::query()

            ->orderBy('name')

            ->get();

    }

    /**
     * Find brand.
     */
    public function find(
        int $id
    ): Brand {

        return Brand::query()

            ->with([
                'creator',
                'updater',
                'products',
            ])

            ->findOrFail($id);

    }

    /**
     * Create brand.
     */
    public function create(
        array $data
    ): Brand {

        return Brand::create($data);

    }

    /**
     * Update brand.
     */
    public function update(
        int $id,
        array $data
    ): Brand {

        $brand = $this->find($id);

        $brand->update($data);

        return $brand->refresh();

    }

    /**
     * Delete brand.
     */
    public function delete(
        int $id
    ): bool {

        return (bool) $this->find($id)
            ->delete();

    }

    /**
     * Restore brand.
     */
    public function restore(
        int $id
    ): bool {

        return (bool) Brand::onlyTrashed()

            ->findOrFail($id)

            ->restore();

    }

    /**
     * Permanently delete brand.
     */
    public function forceDelete(
        int $id
    ): bool {

        return (bool) Brand::onlyTrashed()

            ->findOrFail($id)

            ->forceDelete();

    }

    /**
     * Brand dropdown.
     */
    public function dropdown(): Collection
    {
        return Brand::query()

            ->where(
                'is_active',
                true
            )

            ->orderBy('name')

            ->get([
                'id',
                'name',
            ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    /**
     * Website brand listing.
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator {

        return Brand::query()

            ->withCount('products')

            ->where(
                'is_active',
                true
            )

            ->when(

                !empty($filters['search']),

                fn($query) =>

                $query->where(
                    'name',
                    'ILIKE',
                    '%' . trim($filters['search']) . '%'
                )

            )

            ->orderBy('name')

            ->paginate(
                $filters['per_page'] ?? 20
            );

    }

    /**
     * Find brand by slug.
     */
    public function findBySlug(
        string $slug
    ): Brand {

        return Brand::query()

            ->with([
                'products.images',
                'products.category',
                'products.unit',
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

    /**
     * Featured brands.
     */
    public function featured(): Collection
    {
        return Brand::query()

            ->withCount('products')

            ->where(
                'featured',
                true
            )

            ->where(
                'is_active',
                true
            )

            ->orderBy('name')

            ->get();

    }
}
