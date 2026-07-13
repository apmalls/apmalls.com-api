<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Category\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\CategoryRepositoryInterface;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Category Listing
    |--------------------------------------------------------------------------
    */

    /**
     * Get website categories.
     */
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        return $this->categoryRepository
            ->websitePaginate($filters);

    }

    /*
    |--------------------------------------------------------------------------
    | Featured Categories
    |--------------------------------------------------------------------------
    */

    /**
     * Get featured categories.
     */
    public function featured(): Collection
    {
        return $this->categoryRepository
            ->featured();
    }

    /*
    |--------------------------------------------------------------------------
    | Category Details
    |--------------------------------------------------------------------------
    */

    /**
     * Find category by slug.
     */
    public function show(
        string $slug
    ): Category {

        return $this->categoryRepository
            ->findBySlug($slug);

    }
}
