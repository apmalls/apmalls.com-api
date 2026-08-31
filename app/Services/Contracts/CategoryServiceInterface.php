<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Category\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CategoryServiceInterface
{
    /**
     * Display a listing of categories.
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    /**
     * Get all categories.
     */
    public function all(): Collection;

    /**
     * Get category tree.
     */
    public function tree(): Collection;

    /**
     * Get trashed categories.
     */
    public function trash(array $filters = []): LengthAwarePaginator;

    /**
     * Find category by id.
     */
    public function find(int $id): Category;

    /**
     * Create a category.
     */
    public function create(array $data): Category;

    /**
     * Update a category.
     */
    public function update(int $id, array $data): Category;

    /**
     * Delete a category.
     */
    public function delete(int $id): bool;

    /**
     * Change category status.
     */
    public function changeStatus(int $id, bool $isActive): Category;

    /**
     * Restore a soft deleted category.
     */
    public function restore(int $id): bool;

    /**
     * Force delete a category.
     */
    public function forceDelete(int $id): bool;

    /**
     * Bulk delete categories.
     */
    public function bulkDelete(array $ids): array;

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
     * Active website category tree.
     */
    public function websiteTree(): Collection;

    /**
     * Find category by slug.
     */
    public function findBySlug(
        string $slug
    ): Category;

    /**
     * Featured categories.
     */
    public function featured(
        int $limit = 10
    ): Collection;
}
