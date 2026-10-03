<?php

namespace App\Repositories\Contracts;

use App\Models\Offer\WebsiteOffer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WebsiteOfferRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function active(): Collection;

    public function trashed(): LengthAwarePaginator;

    public function findTrashedById(int $id): WebsiteOffer;

    public function findById(int $id): WebsiteOffer;

    public function create(array $data): WebsiteOffer;

    public function update(int $id, array $data): WebsiteOffer;

    public function delete(int $id): bool;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    public function changeStatus(int $id): WebsiteOffer;

    public function bulkDelete(array $ids): bool;

}
