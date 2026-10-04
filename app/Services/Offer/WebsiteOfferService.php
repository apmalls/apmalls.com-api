<?php

namespace App\Services\Offer;

use App\Models\Offer\WebsiteOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\WebsiteOfferRepositoryInterface;
use App\Services\Contracts\WebsiteOfferServiceInterface;

class WebsiteOfferService implements WebsiteOfferServiceInterface
{
    public function __construct(
        protected WebsiteOfferRepositoryInterface $repository,
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

    public function findTrashedById(int $id): WebsiteOffer
    {
        return $this->repository->findTrashedById($id);
    }

    /**
     * Banner Details
     */
    public function findById(int $id): WebsiteOffer
    {
        return $this->repository->findById($id);
    }

    /**
     * Create Banner
     */
    public function create(array $data): WebsiteOffer
    {
        return DB::transaction(function () use ($data) {

            $data['created_by'] = auth()->id();

            return $this->repository->create($data);

        });
    }

    /**
     * Update Banner
     */
    public function update(int $id, array $data): WebsiteOffer
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
    public function changeStatus(int $id): WebsiteOffer
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

}
