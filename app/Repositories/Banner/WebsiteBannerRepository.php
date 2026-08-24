<?php

namespace App\Repositories\Banner;


use App\Models\Banner\WebsiteBanner;
use App\Repositories\Contracts\WebsiteBannerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class WebsiteBannerRepository implements WebsiteBannerRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return WebsiteBanner::with([
            'createdBy:id,first_name,last_name',
            'updatedBy:id,first_name,last_name'
        ])
            ->search($filters['search'] ?? null)
            ->ordered()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function active(): Collection
    {
        return WebsiteBanner::published()
            ->ordered()
            ->get();
    }

    public function trashed(): LengthAwarePaginator
    {
        return WebsiteBanner::onlyTrashed()
            ->latest()
            ->paginate();
    }

    public function findTrashedById(int $id): WebsiteBanner
    {
        return WebsiteBanner::onlyTrashed()->with([
            'createdBy:id,first_name,last_name',
            'updatedBy:id,first_name,last_name',
        ])->findOrFail($id);
    }

    public function findById(int $id): WebsiteBanner
    {
        return WebsiteBanner::with([
            'createdBy:id,first_name,last_name',
            'updatedBy:id,first_name,last_name'
        ])->findOrFail($id);

    }

    public function create(array $data): WebsiteBanner
    {
        return WebsiteBanner::create($data);
    }

    public function update(int $id, array $data): WebsiteBanner
    {
        $banner = $this->findById($id);

        $banner->update($data);

        return $banner->fresh();
    }

    public function delete(int $id): bool
    {
        return $this->findById($id)->delete();
    }

    public function changeStatus(int $id): WebsiteBanner
    {
        $banner = $this->findById($id);

        $banner->update([
            'status' => !$banner->status,
        ]);

        return $banner->fresh();
    }

    public function restore(int $id): bool
    {
        return WebsiteBanner::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(int $id): bool
    {
        return WebsiteBanner::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();
    }

    public function bulkDelete(array $ids): bool
    {
        WebsiteBanner::whereIn('id', $ids)->delete();

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    /**
     * Homepage sliders.
     */
    public function sliders(): Collection
    {
        return WebsiteBanner::query()

            ->where(
                'banner_type',
                'slider'
            )

            ->where(
                'status',
                true
            )

            ->where(function ($query) {

                $query

                    ->whereNull('start_date')

                    ->orWhere(
                        'start_date',
                        '<=',
                        Carbon::now()
                    );

            })

            ->where(function ($query) {

                $query

                    ->whereNull('end_date')

                    ->orWhere(
                        'end_date',
                        '>=',
                        Carbon::now()
                    );

            })

            ->orderBy('sort_order')

            ->get();
    }

    /**
     * Homepage offer banners.
     */
    public function offerBanners(): Collection
    {
        return WebsiteBanner::query()

            ->where(
                'banner_type',
                'offer'
            )

            ->where(
                'status',
                true
            )

            ->where(function ($query) {

                $query

                    ->whereNull('start_date')

                    ->orWhere(
                        'start_date',
                        '<=',
                        Carbon::now()
                    );

            })

            ->where(function ($query) {

                $query

                    ->whereNull('end_date')

                    ->orWhere(
                        'end_date',
                        '>=',
                        Carbon::now()
                    );

            })

            ->orderBy('sort_order')

            ->get();
    }
}
