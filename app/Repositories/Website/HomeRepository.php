<?php

namespace App\Repositories\Website;

use App\Repositories\Contracts\Website\HomeRepositoryInterface;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\WebsiteBannerRepositoryInterface;

class HomeRepository implements HomeRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository,
        protected BrandRepositoryInterface $brandRepository,
        protected ProductRepositoryInterface $productRepository,
        protected WebsiteBannerRepositoryInterface $bannerRepository,
    ) {}

    /**
     * Website Home Page.
     *
     * @return array<string, mixed>
     */
    public function index(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Sliders
            |--------------------------------------------------------------------------
            */
            'sliders' => $this->bannerRepository->sliders(),

            /*
            |--------------------------------------------------------------------------
            | Offer Banners
            |--------------------------------------------------------------------------
            */
            'offer_banners' => $this->bannerRepository->offerBanners(),

            /*
            |--------------------------------------------------------------------------
            | Featured Categories
            |--------------------------------------------------------------------------
            */
            'featured_categories' => $this->categoryRepository->featured(),

            /*
            |--------------------------------------------------------------------------
            | Featured Brands
            |--------------------------------------------------------------------------
            */
            'featured_brands' => $this->brandRepository->featured(),

            /*
            |--------------------------------------------------------------------------
            | Featured Products
            |--------------------------------------------------------------------------
            */
            'featured_products' => $this->productRepository->featuredProducts(),

            /*
            |--------------------------------------------------------------------------
            | New Arrivals
            |--------------------------------------------------------------------------
            */
            'new_arrivals' => $this->productRepository->newArrivalProducts(),

            /*
            |--------------------------------------------------------------------------
            | Best Sellers
            |--------------------------------------------------------------------------
            */
            'best_sellers' => $this->productRepository->bestSellerProducts(),

            /*
            |--------------------------------------------------------------------------
            | Trending Products
            |--------------------------------------------------------------------------
            */
            'trending_products' => [],

            /*
            |--------------------------------------------------------------------------
            | Recommended Products
            |--------------------------------------------------------------------------
            */
            'recommended_products' => [],

        ];
    }
}
