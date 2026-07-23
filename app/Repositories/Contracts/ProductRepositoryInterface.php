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

    /**
     * Website product listing.
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Find product by slug.
     */
    public function findBySlug(
        string $slug
    ): Product;

    /**
     * Featured products.
     */
    public function featured(
        int $limit = 10
    ): Collection;

    /**
     * New arrival products.
     */
    public function newArrivals(
        int $limit = 10
    ): Collection;

    /**
     * Best seller products.
     */
    public function bestSellers(
        int $limit = 10
    ): Collection;

    /**
     * Related products.
     */
    public function related(
        string $slug,
        int $limit = 10
    ): Collection;

    /**
     * Search products.
     */
    public function search(
        array $filters = []
    ): LengthAwarePaginator;
}
