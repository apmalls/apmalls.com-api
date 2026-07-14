<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Repositories\Contracts\BrandRepositoryInterface;
// use App\Repositories\Contracts\BannerRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;

class HomeService
{
    public function __construct(
        // protected BannerRepositoryInterface $bannerRepository,
        protected CategoryRepositoryInterface $categoryRepository,
        protected ProductRepositoryInterface $productRepository,
        protected BrandRepositoryInterface $brandRepository,
    ) {
    }

    /**
     * Home Page Data
     */
    public function index(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Hero Banners
            |--------------------------------------------------------------------------
            */

            // 'banners' => $this->bannerRepository->active(),

            /*
            |--------------------------------------------------------------------------
            | Featured Categories
            |--------------------------------------------------------------------------
            */

            'featured_categories' => $this->categoryRepository->featured(),

            /*
            |--------------------------------------------------------------------------
            | Featured Products
            |--------------------------------------------------------------------------
            */

            'featured_products' => $this->productRepository->featured(),

            /*
            |--------------------------------------------------------------------------
            | New Arrivals
            |--------------------------------------------------------------------------
            */

            'new_arrivals' => $this->productRepository->newArrivals(),

            /*
            |--------------------------------------------------------------------------
            | Best Sellers
            |--------------------------------------------------------------------------
            */

            'best_sellers' => $this->productRepository->bestSellers(),

            /*
            |--------------------------------------------------------------------------
            | Brands
            |--------------------------------------------------------------------------
            */

            'brands' => $this->brandRepository->featured(),

        ];
    }
}
