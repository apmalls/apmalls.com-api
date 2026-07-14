<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product\Brand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BrandRepositoryInterface
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
    ): LengthAwarePaginator;

    /**
     * Get trashed brands.
     */
    public function trash(
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Get all brands.
     */
    public function all(): Collection;

    /**
     * Find brand.
     */
    public function find(
        int $id
    ): Brand;

    /**
     * Create brand.
     */
    public function create(
        array $data
    ): Brand;

    /**
     * Update brand.
     */
    public function update(
        int $id,
        array $data
    ): Brand;

    /**
     * Delete brand.
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Restore brand.
     */
    public function restore(
        int $id
    ): bool;

    /**
     * Permanently delete brand.
     */
    public function forceDelete(
        int $id
    ): bool;

    /**
     * Brand dropdown.
     */
    public function dropdown(): Collection;

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
    ): LengthAwarePaginator;

    /**
     * Find brand by slug.
     */
    public function findBySlug(
        string $slug
    ): Brand;

    /**
     * Featured brands.
     */
    public function featured(): Collection;
}
