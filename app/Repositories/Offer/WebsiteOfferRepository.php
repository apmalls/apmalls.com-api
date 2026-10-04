<?php

namespace App\Repositories\Offer;


use App\Models\Offer\WebsiteOffer;
use App\Repositories\Contracts\WebsiteOfferRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class WebsiteOfferRepository implements WebsiteOfferRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return WebsiteOffer::with([
            'createdBy:id,first_name,last_name',
            'updatedBy:id,first_name,last_name'
        ])
            ->search($filters['search'] ?? null)
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($query) => $query->where('status', filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN)))
            ->ordered()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function active(): Collection
    {
        return WebsiteOffer::published()
            ->ordered()
            ->get();
    }

    public function trashed(): LengthAwarePaginator
    {
        return WebsiteOffer::onlyTrashed()
            ->latest()
            ->paginate();
    }

    public function findTrashedById(int $id): WebsiteOffer
    {
        return WebsiteOffer::onlyTrashed()->with([
            'createdBy:id,first_name,last_name',
            'updatedBy:id,first_name,last_name',
        ])->findOrFail($id);
    }

    public function findById(int $id): WebsiteOffer
    {
        return WebsiteOffer::with([
            'createdBy:id,first_name,last_name',
            'updatedBy:id,first_name,last_name'
        ])->findOrFail($id);

    }

    public function create(array $data): WebsiteOffer
    {
        return WebsiteOffer::create($data);
    }

    public function update(int $id, array $data): WebsiteOffer
    {
        $banner = $this->findById($id);

        $banner->update($data);

        return $banner->fresh();
    }

    public function delete(int $id): bool
    {
        return $this->findById($id)->delete();
    }

    public function changeStatus(int $id): WebsiteOffer
    {
        $banner = $this->findById($id);

        $banner->update([
            'status' => !$banner->status,
        ]);

        return $banner->fresh();
    }

    public function restore(int $id): bool
    {
        return WebsiteOffer::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(int $id): bool
    {
        return WebsiteOffer::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();
    }

    public function bulkDelete(array $ids): bool
    {
        WebsiteOffer::whereIn('id', $ids)->delete();

        return true;
    }

}
