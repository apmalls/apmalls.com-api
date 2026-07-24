<?php

namespace App\Services\Banner;

use App\Models\Banner\WebsiteBanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\WebsiteBannerRepositoryInterface;
use App\Services\Contracts\WebsiteBannerServiceInterface;

class WebsiteBannerService implements WebsiteBannerServiceInterface
{
    public function __construct(
        protected WebsiteBannerRepositoryInterface $repository,
        protected WebsiteBannerRepositoryInterface $bannerRepository,
    ) {
    }

    /**
     * Banner Listing
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    /**
     * Active Website Banners
     */
    public function active(): Collection
    {
        return $this->repository->active();
    }

    /**
     * Trashed Banners
     */
    public function trashed(): LengthAwarePaginator
    {
        return $this->repository->trashed();
    }

    public function findTrashedById(int $id): WebsiteBanner
    {
        return $this->repository->findTrashedById($id);
    }

    /**
     * Banner Details
     */
    public function findById(int $id): WebsiteBanner
    {
        return $this->repository->findById($id);
    }

    /**
     * Create Banner
     */
    public function create(array $data): WebsiteBanner
    {
        return DB::transaction(function () use ($data) {

            $data['created_by'] = auth()->id();

            return $this->repository->create($data);

        });
    }

    /**
     * Update Banner
     */
    public function update(int $id, array $data): WebsiteBanner
    {
        return DB::transaction(function () use ($id, $data) {

            $data['updated_by'] = auth()->id();

            return $this->repository->update($id, $data);

        });
    }

    /**
     * Soft Delete
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            return $this->repository->delete($id);

        });
    }

    /**
     * Restore Banner
     */
    public function restore(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            return $this->repository->restore($id);

        });
    }

    /**
     * Permanent Delete
     */
    public function forceDelete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            return $this->repository->forceDelete($id);

        });
    }

    /**
     * Change Status
     */
    public function changeStatus(int $id): WebsiteBanner
    {
        return DB::transaction(function () use ($id) {

            return $this->repository->changeStatus($id);

        });
    }

    /**
     * Bulk Delete
     */
    public function bulkDelete(array $ids): bool
    {
        return DB::transaction(function () use ($ids) {

            return $this->repository->bulkDelete($ids);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    /**
     * Get homepage sliders.
     */
    public function sliders(): Collection
    {
        return $this->bannerRepository->sliders();
    }

    /**
     * Get homepage offer banners.
     */
    public function offerBanners(): Collection
    {
        return $this->bannerRepository->offerBanners();
    }
}
