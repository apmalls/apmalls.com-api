<?php

declare(strict_types=1);

namespace App\Services\Unit;

use App\Models\Product\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Services\Contracts\UnitServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UnitService implements UnitServiceInterface
{
    public function __construct(
        protected UnitRepositoryInterface $unitRepository,
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->unitRepository->paginate($filters);
    }

    public function all(): Collection
    {
        return $this->unitRepository->all();
    }

    public function trash(array $filters = []): LengthAwarePaginator
    {
        return $this->unitRepository->trash($filters);
    }

    public function find(int $id): Unit
    {
        return $this->unitRepository->findOrFail($id);
    }

    public function create(array $data): Unit
    {
        return $this->unitRepository->create($data);
    }

    public function update(int $id, array $data): Unit
    {
        return $this->unitRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->unitRepository->delete($id);
    }

    public function changeStatus(int $id, bool $isActive): Unit
    {
        return $this->unitRepository->changeStatus($id, $isActive);
    }

    public function restore(int $id): bool
    {
        return $this->unitRepository->restore($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->unitRepository->forceDelete($id);
    }

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
    ): LengthAwarePaginator {

        return $this->unitRepository->websitePaginate($filters);

    }

    /**
     * Find unit by slug.
     */
    public function findBySlug(
        string $slug
    ): Unit {

        return $this->unitRepository->findBySlug($slug);

    }

    /**
     * Featured units.
     */
    public function featured(
        int $limit = 10
    ): Collection {

        return $this->unitRepository->featured($limit);

    }
}
