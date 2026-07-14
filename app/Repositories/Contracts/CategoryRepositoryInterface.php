<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Category\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoryRepositoryInterface
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
    ): LengthAwarePaginator;

    /**
     * Get trashed categories.
     */
    public function trash(
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Get all categories.
     */
    public function all(): Collection;

    /**
     * Find category by id.
     */
    public function find(
        int $id
    ): Category;

    /**
     * Create category.
     */
    public function create(
        array $data
    ): Category;

    /**
     * Update category.
     */
    public function update(
        int $id,
        array $data
    ): Category;

    /**
     * Delete category.
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Restore category.
     */
    public function restore(
        int $id
    ): bool;

    /**
     * Permanently delete category.
     */
    public function forceDelete(
        int $id
    ): bool;

    /**
     * Category dropdown.
     */
    public function dropdown(): Collection;

    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    /**
     * Website category listing.
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Featured categories.
     */
    public function featured(): Collection;

    /**
     * Find category by slug.
     */
    public function findBySlug(
        string $slug
    ): Category;
}
