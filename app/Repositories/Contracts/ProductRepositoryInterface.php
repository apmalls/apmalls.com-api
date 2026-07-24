<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    /**
     * Get paginated products.
     */
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Get trashed products.
     */
    public function trash(
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Get all products.
     */
    public function all(): Collection;

    /**
     * Find product by id.
     */
    public function find(
        int $id
    ): Product;

    /**
     * Find product by id.
     */
    public function findById(
        int $id
    ): Product;

    /**
     * Find multiple products by ids.
     */
    public function findMany(array $ids): Collection;

    public function findManyByIds(array $ids): Collection;
    /**
     * Create product.
     */
    public function create(
        array $data
    ): Product;

    /**
     * Update product.
     */
    public function update(
        int $id,
        array $data
    ): Product;

    /**
     * Delete product.
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Restore product.
     */
    public function restore(
        int $id
    ): bool;

    /**
     * Permanently delete product.
     */
    public function forceDelete(
        int $id
    ): bool;

    /**
     * Product dropdown.
     */
    public function dropdown(): Collection;

    public function findByBarcode(
        string $barcode
    );

    public function searchForPOS(
        string $keyword
    );

    public function quickProducts(
        int $limit = 20
    );

    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    /*
|--------------------------------------------------------------------------
| Website
|--------------------------------------------------------------------------
*/

    /**
     * Product listing
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Product details
     */
    public function findBySlug(
        string $slug
    ): Product;

    /**
     * Related products
     */
    public function relatedProducts(
        int $categoryId,
        int $productId,
        int $limit = 8
    ): Collection;

    /**
     * Featured products
     */
    public function featuredProducts(
        int $limit = 12
    ): Collection;

    /**
     * New arrivals
     */
    public function newArrivalProducts(
        int $limit = 12
    ): Collection;

    /**
     * Best sellers
     */
    public function bestSellerProducts(
        int $limit = 12
    ): Collection;

    /**
     * Search suggestions
     */
    public function searchSuggestions(
        string $keyword,
        int $limit = 10
    ): Collection;
}
