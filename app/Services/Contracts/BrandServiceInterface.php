<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Product\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BrandServiceInterface
{
    /**
     * Display a listing of brands.
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    /**
     * Get all brands.
     */
    public function all(): Collection;

    /**
     * Get trashed brands.
     */
    public function trash(array $filters = []): LengthAwarePaginator;

    /**
     * Find brand by id.
     */
    public function find(int $id): Brand;

    /**
     * Create a brand.
     */
    public function create(array $data): Brand;

    /**
     * Update a brand.
     */
    public function update(int $id, array $data): Brand;

    /**
     * Delete a brand.
     */
    public function delete(int $id): bool;

    /**
     * Change brand status.
     */
    public function changeStatus(int $id, bool $isActive): Brand;

    /**
     * Restore a soft deleted brand.
     */
    public function restore(int $id): bool;

    /**
     * Force delete a brand.
     */
    public function forceDelete(int $id): bool;

    /**
     * Get active brands for dropdown.
     */
    public function dropdown(): Collection;

    /**
     * Bulk delete brands.
     */
    public function bulkDelete(array $ids): array;

    /**
     * Bulk update brand status.
     */
    public function bulkStatusUpdate(array $ids, bool $isActive): bool;

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
    public function featured(
        int $limit = 10
    ): Collection;
}
