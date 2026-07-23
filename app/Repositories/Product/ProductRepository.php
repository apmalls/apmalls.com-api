<?php

declare(strict_types=1);

namespace App\Repositories\Product;

use App\Models\Product\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

    /**
     * Product Base Query
     */
    private function baseQuery(): Builder
    {
        return Product::query()

            ->with([

                'category',

                'brand',

                'unit',

                'images',

                'creator',

                'updater',

            ]);

    }

    /**
     * Apply Common Filters
     */
    private function applyFilters(
        Builder $query,
        array $filters = []
    ): Builder {

        return $query

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
                                'sku',
                                'ILIKE',
                                '%' . trim($filters['search']) . '%'
                            )

                            ->orWhere(
                                'barcode',
                                'ILIKE',
                                '%' . trim($filters['search']) . '%'
                            );

                    });

                }

            )

            ->when(

                !empty($filters['category_id']),

                fn($query) =>

                $query->where(
                    'category_id',
                    $filters['category_id']
                )

            )

            ->when(

                !empty($filters['brand_id']),

                fn($query) =>

                $query->where(
                    'brand_id',
                    $filters['brand_id']
                )

            )

            ->when(

                isset($filters['is_active']),

                fn($query) =>

                $query->where(
                    'is_active',
                    $filters['is_active']
                )

            );

    }

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    /**
     * Product Listing
     */
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        $query = $this->applyFilters(
            $this->baseQuery(),
            $filters
        );

        return $query

            ->latest()

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

        return Product::onlyTrashed()

            ->with([
                'category',
                'brand',
                'unit',
            ])

            ->when(

                !empty($filters['search']),

                function ($query) use ($filters) {

                    $query->where(

                        'name',

                        'ILIKE',

                        '%' . trim($filters['search']) . '%'

                    );

                }

            )

            ->latest('deleted_at')

            ->paginate(
                $filters['per_page'] ?? 10
            );

    }

    /**
     * Get All Products
     */
    public function all(): Collection
    {
        return $this->baseQuery()

            ->orderBy('name')

            ->get();

    }

    /**
     * Find Product
     */
    public function find(
        int $id
    ): Product {

        return $this->baseQuery()

            ->findOrFail($id);

    }

    /**
     * Find Product By Id
     */
    public function findById(
        int $id
    ): Product {

        return $this->baseQuery()

            ->findOrFail($id);

    }

    public function findMany(array $ids): Collection
    {
        return Product::query()
            ->whereIn('id', $ids)
            ->get();
    }

    public function findManyByIds(array $ids): Collection
    {
        return Product::query()

            ->whereIn('id', $ids)

            ->get()

            ->keyBy('id');
    }

    /**
     * Create Product
     */
    public function create(
        array $data
    ): Product {

        return Product::create($data);

    }

    /**
     * Update Product
     */
    public function update(
        int $id,
        array $data
    ): Product {

        $product = $this->find($id);

        $product->update($data);

        return $product->refresh();

    }

    /**
     * Delete Product
     */
    public function delete(
        int $id
    ): bool {

        return (bool) $this->find($id)
            ->delete();

    }

    /**
     * Restore Product
     */
    public function restore(
        int $id
    ): bool {

        return (bool) Product::onlyTrashed()

            ->findOrFail($id)

            ->restore();

    }

    /**
     * Permanently Delete Product
     */
    public function forceDelete(
        int $id
    ): bool {

        return (bool) Product::onlyTrashed()

            ->findOrFail($id)

            ->forceDelete();

    }

    /**
     * Product Dropdown
     */
    public function dropdown(): Collection
    {
        return Product::query()

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

    public function findByBarcode(
        string $barcode
    ): Product {
        return Product::where('barcode', $barcode)
            ->where('status', 1)
            ->firstOrFail();
    }


    public function searchForPOS(
        string $keyword
    ) {
        return Product::query()

            ->where(function ($query) use ($keyword) {

                $query

                    ->where('name', 'ILIKE', "%{$keyword}%")

                    ->orWhere('sku', 'ILIKE', "%{$keyword}%")

                    ->orWhere('barcode', 'ILIKE', "%{$keyword}%");

            })

            ->where('status', 1)

            ->limit(20)

            ->get();
    }
    public function quickProducts(int $limit = 20): Collection
    {
        return Product::query()
            ->where('status', 1)
            ->latest()
            ->limit($limit)
            ->get();
    }
    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    /**
     * Website Product Listing
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator {

        $query = Product::query()

            ->with([
                'category',
                'brand',
                'unit',
                'images',
            ])

            ->where(
                'is_active',
                true
            );

        $query = $this->applyFilters(
            $query,
            $filters
        );

        return $query

            ->latest()

            ->paginate(
                $filters['per_page'] ?? 20
            );

    }

    /**
     * Find Product By Slug
     */
    public function findBySlug(
        string $slug
    ): Product {

        return Product::query()

            ->with([
                'category',
                'brand',
                'unit',
                'images',
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
     * Featured Products
     */
    public function featured(
        int $limit = 10
    ): Collection {

        return Product::query()

            ->with([
                'category',
                'brand',
                'images',
            ])

            ->where(
                'is_active',
                true
            )

            ->where(
                'featured',
                true
            )

            ->latest()

            ->limit($limit)

            ->get();

    }

    /**
     * New Arrival Products
     */
    public function newArrivals(
        int $limit = 10
    ): Collection {

        return Product::query()

            ->with([
                'category',
                'brand',
                'images',
            ])

            ->where(
                'is_active',
                true
            )

            ->where(
                'new_arrival',
                true
            )

            ->latest()

            ->limit($limit)

            ->get();

    }

    /**
     * Best Seller Products
     */
    public function bestSellers(
        int $limit = 10
    ): Collection {

        return Product::query()

            ->with([
                'category',
                'brand',
                'images',
            ])

            ->where(
                'is_active',
                true
            )

            ->where(
                'best_seller',
                true
            )

            ->latest()

            ->limit($limit)

            ->get();

    }

    /**
     * Related Products
     */
    public function related(
        string $slug,
        int $limit = 10
    ): Collection {

        $product = $this->findBySlug($slug);

        return Product::query()

            ->with([
                'category',
                'brand',
                'images',
            ])

            ->where(
                'is_active',
                true
            )

            ->where(
                'category_id',
                $product->category_id
            )

            ->where(
                'id',
                '!=',
                $product->id
            )

            ->latest()

            ->limit($limit)

            ->get();

    }

    /**
     * Search Products
     */
    public function search(
        array $filters = []
    ): LengthAwarePaginator {

        return $this->websitePaginate(
            $filters
        );

    }
}
