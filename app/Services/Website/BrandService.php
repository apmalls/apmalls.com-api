<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Product\Brand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\BrandRepositoryInterface;

class BrandService
{
    public function __construct(
        protected BrandRepositoryInterface $brandRepository,
    ) {
    }

    /**
     * Brand Listing
     */
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        return $this->brandRepository
            ->websitePaginate($filters);

    }

    /**
     * Brand Details
     */
    public function show(
        string $slug
    ): Brand {

        return $this->brandRepository
            ->findBySlug($slug);

    }

    /**
     * Active Brands
     */
    public function all(): Collection
    {
        return $this->brandRepository
            ->featured();
    }
}
