<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Repositories\Purchase\PurchaseRepository;
use App\Repositories\Contracts\CashRegisterRepositoryInterface;
use App\Repositories\POS\CashRegisterRepository;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Category\CategoryRepository;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Product\ProductRepository;

use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Product\BrandRepository;

use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Cart\CartRepository;

use App\Repositories\Contracts\CartItemRepositoryInterface;
use App\Repositories\Cart\CartItemRepository;

use App\Repositories\Contracts\WishlistRepositoryInterface;
use App\Repositories\Wishlist\WishlistRepository;




class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PaymentRepositoryInterface::class,
            PaymentRepository::class
        );

        $this->app->bind(
            PurchaseRepositoryInterface::class,
            PurchaseRepository::class
        );

        $this->app->bind(
            CashRegisterRepositoryInterface::class,
            CashRegisterRepository::class
        );

        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class
        );

        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        $this->app->bind(
            BrandRepositoryInterface::class,
            BrandRepository::class
        );

        $this->app->bind(
            CartRepositoryInterface::class,
            CartRepository::class
        );

        $this->app->bind(
            CartItemRepositoryInterface::class,
            CartItemRepository::class
        );

        $this->app->bind(
            WishlistRepositoryInterface::class,
            WishlistRepository::class
        );



    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
