<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Product\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductServiceInterface
{
    /**
     * Display a listing of products.
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    /**
     * Get all products.
     */
    public function all(): Collection;

    /**
     * Get trashed products.
     */
    public function trash(array $filters = []): LengthAwarePaginator;

    /**
     * Find product by id.
     */
    public function find(int $id): Product;

    /**
     * Create a product.
     */
    public function create(array $data): Product;

    /**
     * Update a product.
     */
    public function update(int $id, array $data): Product;

    /**
     * Delete a product.
     */
    public function delete(int $id): bool;

    /**
     * Change product status.
     */
    public function changeStatus(int $id, bool $isActive): Product;

    /**
     * Restore a soft deleted product.
     */
    public function restore(int $id): bool;

    /**
     * Force delete a product.
     */
    public function forceDelete(int $id): bool;

    /**
     * Generate unique SKU.
     */
    public function generateSku(): string;

    /**
     * Generate unique barcode.
     */
    public function generateBarcode(): string;

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
     * Product details.
     */
    public function findBySlug(
        string $slug
    ): Product;

    /**
     * Related products.
     */
    public function relatedProducts(
        int $categoryId,
        int $productId,
        int $limit = 8
    ): Collection;

    /**
     * Featured products.
     */
    public function featuredProducts(
        int $limit = 12
    ): Collection;

    /**
     * New arrival products.
     */
    public function newArrivalProducts(
        int $limit = 12
    ): Collection;

    /**
     * Best seller products.
     */
    public function bestSellerProducts(
        int $limit = 12
    ): Collection;

    /**
     * Product search suggestions.
     */
    public function searchSuggestions(
        string $keyword,
        int $limit = 10
    ): Collection;

}
