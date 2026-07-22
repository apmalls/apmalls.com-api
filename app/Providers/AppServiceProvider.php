<?php

namespace App\Providers;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\PaymentGatewayTransactionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Dashboard\DashboardRepository;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Payment\PaymentGatewayTransactionRepository;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Role\RoleRepository;
use App\Services\Contracts\CashRegisterTransactionServiceInterface;
use App\Services\POS\CashRegisterTransactionService;
use Illuminate\Support\ServiceProvider;

use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Contracts\PaymentModeRepositoryInterface;
use App\Repositories\Payment\PaymentModeRepository;
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

use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Customer\CustomerRepository;

use App\Repositories\Contracts\CustomerAddressRepositoryInterface;
use App\Repositories\Customer\CustomerAddressRepository;

use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Repositories\Sale\SaleRepository;

use App\Repositories\Contracts\SaleOrderItemRepositoryInterface;
use App\Repositories\Sale\SaleOrderItemRepository;

use App\Repositories\Contracts\SaleReturnRepositoryInterface;
use App\Repositories\Sale\SaleReturnRepository;

use App\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use App\Repositories\Purchase\PurchaseReturnRepository;

use App\Repositories\Contracts\StockRepositoryInterface;
use App\Repositories\Inventory\StockRepository;

use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Inventory\StockMovementRepository;

use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Repositories\Inventory\StockAdjustmentRepository;

use App\Services\Contracts\PurchaseServiceInterface;
use App\Services\Purchase\PurchaseService;

use App\Services\Contracts\PurchaseReturnServiceInterface;
use App\Services\Purchase\PurchaseReturnService;

use App\Services\Contracts\SaleServiceInterface;
use App\Services\Sale\SaleService;

use App\Services\Contracts\SaleReturnServiceInterface;
use App\Services\Sale\SaleReturnService;

use App\Services\Contracts\PaymentServiceInterface;
use App\Services\Payment\PaymentService;

use App\Services\Contracts\StockServiceInterface;
use App\Services\Inventory\StockService;

use App\Services\Contracts\StockMovementServiceInterface;
use App\Services\Inventory\StockMovementService;

use App\Services\Contracts\StockAdjustmentServiceInterface;
use App\Services\Inventory\StockAdjustmentService;

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
            PaymentModeRepositoryInterface::class,
            PaymentModeRepository::class
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

        $this->app->bind(
            CustomerRepositoryInterface::class,
            CustomerRepository::class
        );

        $this->app->bind(
            CustomerAddressRepositoryInterface::class,
            CustomerAddressRepository::class
        );

        // Sale
        $this->app->bind(
            SaleRepositoryInterface::class,
            SaleRepository::class
        );

        $this->app->bind(
            SaleOrderItemRepositoryInterface::class,
            SaleOrderItemRepository::class
        );

        $this->app->bind(
            SaleReturnRepositoryInterface::class,
            SaleReturnRepository::class
        );

        // Purchase
        $this->app->bind(
            PurchaseReturnRepositoryInterface::class,
            PurchaseReturnRepository::class
        );

        // Inventory
        $this->app->bind(
            StockRepositoryInterface::class,
            StockRepository::class
        );

        $this->app->bind(
            StockMovementRepositoryInterface::class,
            StockMovementRepository::class
        );

        $this->app->bind(
            StockAdjustmentRepositoryInterface::class,
            StockAdjustmentRepository::class
        );

        $this->app->bind(
            PaymentGatewayTransactionRepositoryInterface::class,
            PaymentGatewayTransactionRepository::class
        );

        $this->app->bind(
            DashboardRepositoryInterface::class,
            DashboardRepository::class
        );

        $this->app->bind(
            PermissionRepositoryInterface::class,
            PermissionRepository::class
        );

        // Role
        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );

        // Services
        $this->app->bind(
            PurchaseServiceInterface::class,
            PurchaseService::class
        );

        $this->app->bind(
            PurchaseReturnServiceInterface::class,
            PurchaseReturnService::class
        );

        $this->app->bind(
            SaleServiceInterface::class,
            SaleService::class
        );

        $this->app->bind(
            SaleReturnServiceInterface::class,
            SaleReturnService::class
        );

        $this->app->bind(
            PaymentServiceInterface::class,
            PaymentService::class
        );

        $this->app->bind(
            StockServiceInterface::class,
            StockService::class
        );

        $this->app->bind(
            StockMovementServiceInterface::class,
            StockMovementService::class
        );

        $this->app->bind(
            StockAdjustmentServiceInterface::class,
            StockAdjustmentService::class
        );

        $this->app->bind(
            CashRegisterTransactionServiceInterface::class,
            CashRegisterTransactionService::class
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
