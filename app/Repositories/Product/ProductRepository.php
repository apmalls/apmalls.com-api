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
            ->where('is_active', 1)
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
     * Website product listing.
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator {

        return Product::query()

            ->with([
                'category',
                'brand',
                'unit',
                'images',
            ])

            ->where(
                'is_active',
                true
            )

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

                isset($filters['featured']),

                fn($query) =>

                $query->where(
                    'featured',
                    $filters['featured']
                )

            )

            ->when(

                isset($filters['new_arrival']),

                fn($query) =>

                $query->where(
                    'new_arrival',
                    $filters['new_arrival']
                )

            )

            ->when(

                isset($filters['best_seller']),

                fn($query) =>

                $query->where(
                    'best_seller',
                    $filters['best_seller']
                )

            )

            ->when(

                !empty($filters['min_price']),

                fn($query) =>

                $query->where(
                    'selling_price',
                    '>=',
                    $filters['min_price']
                )

            )

            ->when(

                !empty($filters['max_price']),

                fn($query) =>

                $query->where(
                    'selling_price',
                    '<=',
                    $filters['max_price']
                )

            )

            ->tap(function ($query) use ($filters) {

                match ($filters['sort'] ?? 'latest') {

                    'oldest' => $query->oldest(),

                    'price_low_to_high' => $query->orderBy('selling_price'),

                    'price_high_to_low' => $query->orderByDesc('selling_price'),

                    'name_asc' => $query->orderBy('name'),

                    'name_desc' => $query->orderByDesc('name'),

                    default => $query->latest(),

                };

            })

            ->paginate(
                $filters['per_page'] ?? 20
            );

    }

    /**
     * Find product by slug.
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

                'creator',

                'updater',

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
     * Related products.
     */
    public function relatedProducts(
        int $categoryId,
        int $productId,
        int $limit = 8
    ): Collection {

        return Product::query()

            ->with([

                'category',

                'brand',

                'unit',

                'images',

            ])

            ->where(
                'category_id',
                $categoryId
            )

            ->where(
                'id',
                '!=',
                $productId
            )

            ->where(
                'is_active',
                true
            )

            ->latest()

            ->limit($limit)

            ->get();

    }

    /**
     * Featured products.
     */
    public function featuredProducts(
        int $limit = 12
    ): Collection {

        return Product::query()

            ->with([

                'category',

                'brand',

                'unit',

                'images',

            ])

            ->where(
                'featured',
                true
            )

            ->where(
                'is_active',
                true
            )

            ->latest()

            ->limit($limit)

            ->get();

    }

    /**
     * New arrival products.
     */
    public function newArrivalProducts(
        int $limit = 12
    ): Collection {

        return Product::query()

            ->with([

                'category',

                'brand',

                'unit',

                'images',

            ])

            ->where(
                'new_arrival',
                true
            )

            ->where(
                'is_active',
                true
            )

            ->latest()

            ->limit($limit)

            ->get();

    }

    /**
     * Best seller products.
     */
    public function bestSellerProducts(
        int $limit = 12
    ): Collection {

        return Product::query()

            ->with([

                'category',

                'brand',

                'unit',

                'images',

            ])

            ->where(
                'best_seller',
                true
            )

            ->where(
                'is_active',
                true
            )

            ->orderByDesc(
                'sale_count'
            )

            ->limit($limit)

            ->get();

    }

    /**
     * Product search suggestions.
     */
    public function searchSuggestions(
        string $keyword,
        int $limit = 10
    ): Collection {

        return Product::query()

            ->where(
                'is_active',
                true
            )

            ->where(function ($query) use ($keyword) {

                $query

                    ->where(
                        'name',
                        'ILIKE',
                        '%' . trim($keyword) . '%'
                    )

                    ->orWhere(
                        'sku',
                        'ILIKE',
                        '%' . trim($keyword) . '%'
                    )

                    ->orWhere(
                        'barcode',
                        'ILIKE',
                        '%' . trim($keyword) . '%'
                    );

            })

            ->orderBy(
                'name'
            )

            ->limit($limit)

            ->get([
                'id',
                'name',
                'slug',
                'thumbnail',
                'selling_price',
            ]);

    }

}
