<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UnitRepositoryInterface
{
    /**
     * Get paginated units.
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    /**
     * Get all units.
     */
    public function all(): Collection;

    /**
     * Get trashed units.
     */
    public function trash(array $filters = []): LengthAwarePaginator;

    /**
     * Find unit by id.
     */
    public function find(int $id): ?Unit;

    /**
     * Find unit by id or fail.
     */
    public function findOrFail(int $id): Unit;

    /**
     * Create a unit.
     */
    public function create(array $data): Unit;

    /**
     * Update a unit.
     */
    public function update(int $id, array $data): Unit;

    /**
     * Delete a unit.
     */
    public function delete(int $id): bool;

    /**
     * Restore a soft deleted unit.
     */
    public function restore(int $id): bool;

    /**
     * Force delete a unit.
     */
    public function forceDelete(int $id): bool;

    /**
     * Change unit status.
     */
    public function changeStatus(int $id, bool $isActive): Unit;

    /*
|--------------------------------------------------------------------------
| Website
|--------------------------------------------------------------------------
*/

    /**
     * Website unit listing.
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Find unit by slug.
     */
    public function findBySlug(
        string $slug
    ): Unit;

    /**
     * Featured units.
     */
    public function featured(
        int $limit = 10
    ): Collection;
}
