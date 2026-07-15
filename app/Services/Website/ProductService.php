<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\ProductRepositoryInterface;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Product Listing
    |--------------------------------------------------------------------------
    */

    /**
     * Get website products.
     */
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        return $this->productRepository
            ->websitePaginate($filters);

    }

    /*
    |--------------------------------------------------------------------------
    | Product Details
    |--------------------------------------------------------------------------
    */

    /**
     * Find product by slug.
     */
    public function show(
        string $slug
    ): Product {

        return $this->productRepository
            ->findBySlug($slug);

    }

    /*
    |--------------------------------------------------------------------------
    | Featured Products
    |--------------------------------------------------------------------------
    */

    /**
     * Get featured products.
     */
    public function featured(
        int $limit = 10
    ): Collection {

        return $this->productRepository
            ->featured($limit);

    }

    /*
    |--------------------------------------------------------------------------
    | New Arrival Products
    |--------------------------------------------------------------------------
    */

    /**
     * Get new arrival products.
     */
    public function newArrivals(
        int $limit = 10
    ): Collection {

        return $this->productRepository
            ->newArrivals($limit);

    }

    /*
    |--------------------------------------------------------------------------
    | Best Seller Products
    |--------------------------------------------------------------------------
    */

    /**
     * Get best seller products.
     */
    public function bestSellers(
        int $limit = 10
    ): Collection {

        return $this->productRepository
            ->bestSellers($limit);

    }

    /*
    |--------------------------------------------------------------------------
    | Related Products
    |--------------------------------------------------------------------------
    */

    /**
     * Get related products.
     */
    public function related(
        string $slug,
        int $limit = 10
    ): Collection {

        return $this->productRepository
            ->related(
                $slug,
                $limit
            );

    }

    /*
    |--------------------------------------------------------------------------
    | Search Products
    |--------------------------------------------------------------------------
    */

    /**
     * Search products.
     */
    public function search(
        array $filters = []
    ): LengthAwarePaginator {

        return $this->productRepository
            ->search($filters);

    }
}
