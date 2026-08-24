<?php

declare(strict_types=1);

namespace App\Services\Brand;

use App\Models\Product\Brand;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Services\Contracts\BrandServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BrandService implements BrandServiceInterface
{
    public function __construct(
        protected BrandRepositoryInterface $brandRepository,
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->brandRepository->paginate($filters);
    }

    public function all(): Collection
    {
        return $this->brandRepository->all();
    }

    public function trash(array $filters = []): LengthAwarePaginator
    {
        return $this->brandRepository->trash($filters);
    }

    public function find(int $id): Brand
    {
        return $this->brandRepository->find($id);
    }

    public function create(array $data): Brand
    {
        return $this->brandRepository->create($data);
    }

    public function update(int $id, array $data): Brand
    {
        return $this->brandRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->brandRepository->delete($id);
    }

    public function changeStatus(int $id, bool $isActive): Brand
    {
        return $this->brandRepository->find($id);
    }

    public function restore(int $id): bool
    {
        return $this->brandRepository->restore($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->brandRepository->forceDelete($id);
    }

    public function dropdown(): Collection
    {
        return $this->brandRepository->dropdown();
    }

    public function bulkDelete(array $ids): array
    {
        $brands = Brand::whereIn('id', $ids)->get();
        $deleted = 0;
        $failed = [];

        foreach ($brands as $brand) {
            if ($brand->products()->exists()) {
                $failed[] = [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'reason' => 'Has associated products'
                ];
                continue;
            }

            if (!empty($brand->logo)) {
                // File cleanup should be handled in controller
            }

            $brand->delete();
            $deleted++;
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
        ];
    }

    public function bulkStatusUpdate(array $ids, bool $isActive): bool
    {
        return Brand::whereIn('id', $ids)
            ->update([
                'is_active' => $isActive,
                'updated_by' => auth()->id(),
            ]);
    }

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
    ): LengthAwarePaginator {

        return $this->brandRepository->websitePaginate($filters);

    }

    /**
     * Find brand by slug.
     */
    public function findBySlug(
        string $slug
    ): Brand {

        return $this->brandRepository->findBySlug($slug);

    }

    /**
     * Featured brands.
     */
    public function featured(
        int $limit = 10
    ): Collection {

        return $this->brandRepository->featured($limit);

    }
}
