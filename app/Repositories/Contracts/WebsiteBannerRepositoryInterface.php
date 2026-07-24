<?php

namespace App\Repositories\Contracts;

use App\Models\Banner\WebsiteBanner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WebsiteBannerRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function active(): Collection;

    public function trashed(): LengthAwarePaginator;

    public function findTrashedById(int $id): WebsiteBanner;

    public function findById(int $id): WebsiteBanner;

    public function create(array $data): WebsiteBanner;

    public function update(int $id, array $data): WebsiteBanner;

    public function delete(int $id): bool;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    public function changeStatus(int $id): WebsiteBanner;

    public function bulkDelete(array $ids): bool;

    /*
   |--------------------------------------------------------------------------
   | Website
   |--------------------------------------------------------------------------
   */

    /**
     * Homepage sliders.
     */
    public function sliders(): Collection;

    /**
     * Homepage offer banners.
     */
    public function offerBanners(): Collection;
}
