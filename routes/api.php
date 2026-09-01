<?php


use App\Http\Controllers\Api\V1\Admin\Banner\WebsiteBannerController;
use App\Http\Controllers\Api\V1\Admin\Barcode\BarcodePrintController;
use App\Http\Controllers\Api\V1\Admin\Delivery\DeliveryAssignmentController;
use App\Http\Controllers\Api\V1\Admin\Delivery\DeliveryConfirmationController as AdminDeliveryConfirmationController;
use App\Http\Controllers\Api\V1\Admin\Delivery\DeliveryBoyController;
use App\Http\Controllers\Api\V1\Admin\POS\POSController;
use App\Http\Controllers\Api\V1\Admin\Purchase\PurchaseReturnController;
use App\Http\Controllers\Api\V1\Admin\Sale\SaleController;
use App\Http\Controllers\Api\V1\Admin\Sale\SaleReturnController;
use App\Http\Controllers\Api\V1\Admin\Inventory\StockAdjustmentController;
use App\Http\Controllers\Api\V1\Admin\Inventory\StockController;
use App\Http\Controllers\Api\V1\Admin\Inventory\StockMovementController;
use App\Http\Controllers\Api\V1\Admin\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Admin\Payment\PaymentModeController;
use App\Http\Controllers\Api\V1\Admin\Permission\PermissionController;
use App\Http\Controllers\Api\V1\Admin\Purchase\PurchaseOrderController;
use App\Http\Controllers\API\V1\Admin\Setting\GeneralSettingController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Admin\Barcode\BarcodeTemplateController;
use App\Http\Controllers\Api\V1\Admin\Barcode\ProductBarcodeController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Auth\OtpAuthController;
use App\Http\Controllers\Api\V1\Customer\CustomerController;

use App\Http\Controllers\Api\V1\Customer\CustomerAddressController;

use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Delivery\DeliveryPortalController;


use App\Http\Controllers\Api\V1\Admin\Product\BrandController;
use App\Http\Controllers\Api\V1\Admin\Product\CategoryController;
use App\Http\Controllers\Api\V1\Admin\Product\ProductController;
use App\Http\Controllers\Api\V1\Admin\Product\ProductImageController;
use App\Http\Controllers\Api\V1\Admin\Product\UnitController;
use App\Http\Controllers\Api\V1\Admin\SystemMaintenance\SystemMaintenanceController;
use App\Http\Controllers\Api\V1\Role\RoleController;


use App\Http\Controllers\Api\V1\Supplier\SupplierAddressController;
use App\Http\Controllers\Api\V1\Supplier\SupplierController;
use App\Http\Controllers\Api\V1\Admin\User\UserController;

use App\Http\Controllers\Api\V1\Website\CartController;
use App\Http\Controllers\Api\V1\Website\CategoryController as WebsiteCategoryController;
use App\Http\Controllers\Api\V1\Website\BrandController as WebsiteBrandController;
use App\Http\Controllers\Api\V1\Website\HomeController;
use App\Http\Controllers\Api\V1\Website\ProductController as WebsiteProductController;
use App\Http\Controllers\Api\V1\Website\UnitController as WebsiteUnitController;
use App\Http\Controllers\Api\V1\Website\CustomerAddressController as WebsiteCustomerAddressController;
use App\Http\Controllers\Api\V1\Website\SaleOrderController as WebsiteSaleOrderController;
use App\Http\Controllers\Api\V1\Website\WishlistController;
use App\Http\Controllers\Api\V1\Website\PaymentController as WebsitePaymentController;
use App\Http\Controllers\Api\V1\Website\CheckoutController;
use App\Http\Controllers\Api\V1\Website\DeliveryConfirmationController as WebsiteDeliveryConfirmationController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Version 1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    Route::middleware([
        'auth:sanctum',
        'permission:dashboard.view'
    ])->get('/dashboard', DashboardController::class);

    Route::middleware(['auth:sanctum', 'role:Delivery Boy'])
        ->prefix('delivery')
        ->group(function () {
            Route::get('/assignments', [DeliveryPortalController::class, 'index'])
                ->middleware('permission:delivery-assignment.list');
            Route::patch('/profile/availability', [DeliveryPortalController::class, 'availability'])
                ->middleware('permission:delivery-assignment.update');
            Route::get('/assignments/{id}', [DeliveryPortalController::class, 'show'])
                ->middleware('permission:delivery-assignment.view');
            Route::patch('/assignments/{id}/{action}', [DeliveryPortalController::class, 'action'])
                ->whereIn('action', ['accept', 'reject', 'pickup', 'out-for-delivery', 'out_for_delivery', 'delivered'])
                ->middleware('permission:delivery-assignment.update');
            Route::post('/assignments/{id}/confirm-otp', [DeliveryPortalController::class, 'confirmOtp'])
                ->middleware('permission:delivery-assignment.update');
        });

    Route::middleware('auth:sanctum')
        ->prefix('permissions')
        ->group(function () {

            Route::get('/', [PermissionController::class, 'index'])
                ->middleware('permission:permission.view');

            Route::get('/grouped', [PermissionController::class, 'grouped'])
                ->middleware('permission:permission.view|role.view');

            Route::get('/{id}', [PermissionController::class, 'show'])
                ->middleware('permission:permission.view');

            Route::post('/', [PermissionController::class, 'store'])
                ->middleware('permission:permission.create');

            Route::put('/{id}', [PermissionController::class, 'update'])
                ->middleware('permission:permission.update');

            Route::delete('/{id}', [PermissionController::class, 'destroy'])
                ->middleware('permission:permission.delete');
        });
    Route::prefix('roles')
        ->middleware('auth:sanctum')
        ->controller(RoleController::class)
        ->group(function () {

            Route::get('/', 'index')->middleware('permission:role.view');

            Route::post('/', 'store')->middleware('permission:role.create');

            Route::get('/{id}', 'show')->middleware('permission:role.view');

            Route::put('/{id}', 'update')->middleware('permission:role.update');

            Route::delete('/{id}', 'destroy')->middleware('permission:role.delete');
        });
    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('/auth')->group(function () {

        Route::post('/register', [AuthController::class, 'register']);

        Route::post('/login', [AuthController::class, 'login']);

        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::post(
            'send-otp',
            [OtpAuthController::class, 'sendOtp']
        );

        Route::post(
            'verify-otp',
            [OtpAuthController::class, 'verifyOtp']
        );
    });

    Route::prefix('auth')->group(function () {

        Route::get('/google', [GoogleAuthController::class, 'redirect']);

        Route::get('/google/callback', [GoogleAuthController::class, 'callback']);
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')
        ->prefix('auth')
        ->group(function () {

            Route::post('/logout', [AuthController::class, 'logout']);

            Route::get('/profile', [AuthController::class, 'profile']);

            Route::put('/profile', [AuthController::class, 'updateProfile']);

            Route::put('/change-password', [AuthController::class, 'changePassword']);
        });

    Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {

        Route::middleware([
            'role:Super Admin'
        ])->group(function () {

            Route::post(
                'system/refresh',
                [SystemMaintenanceController::class, 'refresh']
            );
        });

        /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */

        Route::prefix('users')->controller(UserController::class)->group(function () {

            Route::get('/', 'index')->middleware('permission:user.view');

            Route::post('/', 'store')->middleware('permission:user.create');

            Route::get('/trash', 'trash')->middleware('permission:user.view');

            Route::get('/{id}', 'show')->middleware('permission:user.view');

            Route::put('/{id}', 'update')->middleware('permission:user.update');

            Route::delete('/{id}', 'destroy')->middleware('permission:user.delete');

            Route::patch('/{id}/status', 'changeStatus')->middleware('permission:user.change-status');

            Route::put('/{id}/restore', 'restore')->middleware('permission:user.restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:user.force-delete');
        });


        /*
       |--------------------------------------------------------------------------
       | Product Category
       |--------------------------------------------------------------------------
       */
        Route::prefix('categories')->controller(CategoryController::class)->group(function () {
            Route::get('/', 'index')->middleware('permission:category.view');                    // Category List
            Route::post('/', 'store')->middleware('permission:category.create');                   // Create Category
            Route::get('/tree', 'tree')->middleware('permission:category.view');                 // Category Tree
            Route::get('/trash', 'trash')->middleware('permission:category.view');               // Trashed Categories
            Route::get('/{id}', 'show')->middleware('permission:category.view');                 // Category Details
            Route::put('/{id}', 'update')->middleware('permission:category.update');               // Update Category
            Route::delete('/{id}', 'destroy')->middleware('permission:category.delete');           // Delete Category
            Route::patch('/{id}/status', 'changeStatus')->middleware('permission:category.update'); // Change Status
            Route::put('/{id}/restore', 'restore')->middleware('permission:category.restore');      // Restore Category
            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:category.force-delete'); // Force Delete
            Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:category.delete');   // Bulk Delete
        });


        Route::prefix('brands')->controller(BrandController::class)->group(function () {
            Route::get('/', 'index')->middleware('permission:brand.list|brand.view');
            Route::get('/dropdown', 'dropdown')->middleware('permission:brand.list|brand.view');
            Route::get('/trash', 'trash')->middleware('permission:brand.view');
            Route::post('/', 'store')->middleware('permission:brand.create');
            Route::get('/{id}', 'show')->middleware('permission:brand.view');
            Route::put('/{id}', 'update')->middleware('permission:brand.update');
            Route::delete('/{id}', 'destroy')->middleware('permission:brand.delete');
            Route::patch('/{id}/status', 'changeStatus')->middleware('permission:brand.change-status');
            Route::put('/{id}/restore', 'restore')->middleware('permission:brand.restore');
            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:brand.force-delete');
            Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:brand.delete');
            Route::patch('/bulk-status', 'bulkStatusUpdate')->middleware('permission:brand.change-status');
        });

        Route::prefix('units')->controller(UnitController::class)->group(function () {

            Route::get('/', 'index')->middleware('permission:unit.list|unit.view');

            Route::post('/', 'store')->middleware('permission:unit.create');

            Route::get('/trash', 'trash')->middleware('permission:unit.view');

            Route::get('/{id}', 'show')->middleware('permission:unit.view');

            Route::put('/{id}', 'update')->middleware('permission:unit.update');

            Route::delete('/{id}', 'destroy')->middleware('permission:unit.delete');

            Route::patch('/{id}/status', 'changeStatus')->middleware('permission:unit.change-status');

            Route::put('/{id}/restore', 'restore')->middleware('permission:unit.restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:unit.force-delete');
        });


        Route::prefix('products')->controller(ProductController::class)->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Product CRUD
            |--------------------------------------------------------------------------
            */

            Route::get('/', 'index')->middleware('permission:product.list|product.view');

            Route::post('/', 'store')->middleware('permission:product.create');

            Route::get('/trash', 'trash')->middleware('permission:product.view');

            Route::get('/{id}', 'show')->middleware('permission:product.view');

            Route::put('/{id}', 'update')->middleware('permission:product.update');

            Route::delete('/{id}', 'destroy')->middleware('permission:product.delete');

            Route::patch('/{id}/status', 'changeStatus')->middleware('permission:product.change-status');

            Route::put('/{id}/restore', 'restore')->middleware('permission:product.restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:product.force-delete');

            Route::controller(ProductImageController::class)->group(function () {

                /*
                |--------------------------------------------------------------------------
                | Product Gallery
                |--------------------------------------------------------------------------
                */

                Route::get('/{product}/images', 'index')->middleware('permission:product-image.list|product-image.view');

                Route::post('/{product}/images', 'store')->middleware('permission:product-image.create');

                Route::put('/images/{image}', 'update')->middleware('permission:product-image.update');

                Route::delete('/images/{image}', 'destroy')->middleware('permission:product-image.delete');

                Route::patch('/images/{image}/sort-order', 'updateSortOrder')->middleware('permission:product-image.update');
            });
        });

        Route::prefix('suppliers')->controller(SupplierController::class)->group(function () {

            Route::get('/', 'index')->middleware('permission:supplier.list|supplier.view');

            Route::post('/', 'store')->middleware('permission:supplier.create');

            Route::get('/trash', 'trash')->middleware('permission:supplier.view');

            Route::get('/{id}', 'show')->middleware('permission:supplier.view');

            Route::put('/{id}', 'update')->middleware('permission:supplier.update');

            Route::delete('/{id}', 'destroy')->middleware('permission:supplier.delete');

            Route::patch('/{id}/status', 'changeStatus')->middleware('permission:supplier.change-status');

            Route::put('/{id}/restore', 'restore')->middleware('permission:supplier.restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:supplier.force-delete');

            Route::controller(SupplierAddressController::class)->group(function () {

                Route::get('{supplier}/addresses', 'index')->middleware('permission:supplier-address.list|supplier-address.view');

                Route::post('{supplier}/addresses', 'store')->middleware('permission:supplier-address.create');

                Route::get('addresses/trash/{supplier}', 'trash')->middleware('permission:supplier-address.view');

                Route::get('addresses/{id}', 'show')->middleware('permission:supplier-address.view');

                Route::put('addresses/{id}', 'update')->middleware('permission:supplier-address.update');

                Route::delete('addresses/{id}', 'destroy')->middleware('permission:supplier-address.delete');

                Route::patch('addresses/{id}/default', 'changeDefault')->middleware('permission:supplier-address.update');

                Route::put('addresses/{id}/restore', 'restore')->middleware('permission:supplier-address.restore');

                Route::delete('addresses/{id}/force-delete', 'forceDelete')->middleware('permission:supplier-address.force-delete');
            });
        });

        Route::prefix('customers')->controller(CustomerController::class)->group(function () {

            Route::get('/', 'index')->middleware('permission:customer.list|customer.view');

            Route::post('/', 'store')->middleware('permission:customer.create');

            Route::get('/trash', 'trash')->middleware('permission:customer.view');

            Route::get('/{id}', 'show')->middleware('permission:customer.view');

            Route::put('/{id}', 'update')->middleware('permission:customer.update');

            Route::delete('/{id}', 'destroy')->middleware('permission:customer.delete');

            Route::patch('/{id}/status', 'changeStatus')->middleware('permission:customer.change-status');

            Route::put('/{id}/restore', 'restore')->middleware('permission:customer.restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:customer.force-delete');

            Route::controller(CustomerAddressController::class)->group(function () {

                Route::get('{customer}/addresses', 'index')->middleware('permission:customer-address.list|customer-address.view');

                Route::post('{customer}/addresses', 'store')->middleware('permission:customer-address.create');

                Route::get('addresses/trash/{customer}', 'trash')->middleware('permission:customer-address.view');

                Route::get('addresses/{id}', 'show')->middleware('permission:customer-address.view');

                Route::put('addresses/{id}', 'update')->middleware('permission:customer-address.update');

                Route::delete('addresses/{id}', 'destroy')->middleware('permission:customer-address.delete');

                Route::patch('addresses/{id}/default', 'changeDefault')->middleware('permission:customer-address.update');

                Route::put('addresses/{id}/restore', 'restore')->middleware('permission:customer-address.restore');

                Route::delete('addresses/{id}/force-delete', 'forceDelete')->middleware('permission:customer-address.force-delete');
            });
        });


        Route::prefix('purchases')->controller(PurchaseOrderController::class)->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Listing
            |--------------------------------------------------------------------------
            */

            Route::get('/', 'index')->middleware('permission:purchase-order.view');

            Route::get('/all', 'all')->middleware('permission:purchase-order.view');

            Route::get('/trashed', 'trashed')->middleware('permission:purchase-order.view');

            Route::get('/count', 'count')->middleware('permission:purchase-order.view');

            Route::get('/total-amount', 'totalAmount')->middleware('permission:purchase-order.view');

            /*
            |--------------------------------------------------------------------------
            | CRUD
            |--------------------------------------------------------------------------
            */

            Route::post('/', 'store')->middleware('permission:purchase-order.create');

            Route::get('/{id}', 'show')->middleware('permission:purchase-order.view');

            Route::put('/{id}', 'update')->middleware('permission:purchase-order.update');

            Route::delete('/{id}', 'destroy')->middleware('permission:purchase-order.delete');

            /*
            |--------------------------------------------------------------------------
            | Restore
            |--------------------------------------------------------------------------
            */

            Route::post('/restore/{id}', 'restore')->middleware('permission:purchase-order.restore');

            Route::delete('/force-delete/{id}', 'forceDelete')->middleware('permission:purchase-order.force-delete');

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            Route::patch('/status/{id}', 'changeStatus')->middleware('permission:purchase-order.change-status');
        });



        Route::prefix('sales')->controller(SaleController::class)->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Reports
            |--------------------------------------------------------------------------
            */

            Route::get('/count', 'count')->middleware('permission:sale-order.view');

            Route::get('/total-amount', 'totalAmount')->middleware('permission:sale-order.view');

            /*
            |--------------------------------------------------------------------------
            | Trash
            |--------------------------------------------------------------------------
            */

            Route::get('/trash', 'trash')->middleware('permission:sale-order.view');

            Route::patch('/{id}/restore', 'restore')->middleware('permission:sale-order.restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:sale-order.force-delete');

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            Route::patch('/{id}/status', 'changeStatus')->middleware('permission:sale-order.change-status');

            /*
            |--------------------------------------------------------------------------
            | CRUD
            |--------------------------------------------------------------------------
            */

            Route::get('/', 'index')->middleware('permission:sale-order.view');

            Route::post('/', 'store')->middleware('permission:sale-order.create');

            Route::get('/{id}', 'show')->middleware('permission:sale-order.view');

            Route::put('/{id}', 'update')->middleware('permission:sale-order.update');

            Route::delete('/{id}', 'destroy')->middleware('permission:sale-order.delete');
        });


        Route::prefix('purchase-returns')
            ->controller(PurchaseReturnController::class)
            ->group(function () {

                /*
                |--------------------------------------------------------------------------
                | Listing
                |--------------------------------------------------------------------------
                */

                Route::get('/', 'index')
                    ->middleware('permission:purchase-return.list|purchase-return.view');

                Route::get('/trash', 'trash')
                    ->middleware('permission:purchase-return.view');

                Route::get('/count', 'count')
                    ->middleware('permission:purchase-return.list|purchase-return.view');

                Route::get('/total-amount', 'totalAmount')
                    ->middleware('permission:purchase-return.list|purchase-return.view');

                /*
                |--------------------------------------------------------------------------
                | CRUD
                |--------------------------------------------------------------------------
                */

                Route::post('/', 'store')
                    ->middleware('permission:purchase-return.create');

                Route::get('/{id}', 'show')
                    ->middleware('permission:purchase-return.view');

                Route::put('/{id}', 'update')
                    ->middleware('permission:purchase-return.update');

                Route::patch('/{id}/status', 'changeStatus')
                    ->middleware('permission:purchase-return.change-status');

                Route::delete('/{id}', 'destroy')
                    ->middleware('permission:purchase-return.delete');

                /*
                |--------------------------------------------------------------------------
                | Restore
                |--------------------------------------------------------------------------
                */

                Route::patch('/{id}/restore', 'restore')
                    ->middleware('permission:purchase-return.restore');

                Route::delete('/{id}/force-delete', 'forceDelete')
                    ->middleware('permission:purchase-return.force-delete');
            });


        Route::prefix('sale-returns')->controller(SaleReturnController::class)->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Listing
            |--------------------------------------------------------------------------
            */

            Route::get('/', 'index')
                ->middleware('permission:sale-return.list|sale-return.view');

            Route::get('/trash', 'trash')
                ->middleware('permission:sale-return.view');

            Route::get('/count', 'count')
                ->middleware('permission:sale-return.list|sale-return.view');

            Route::get('/total-amount', 'totalAmount')
                ->middleware('permission:sale-return.list|sale-return.view');

            /*
            |--------------------------------------------------------------------------
            | CRUD
            |--------------------------------------------------------------------------
            */

            Route::post('/', 'store')
                ->middleware('permission:sale-return.create');

            Route::get('/{id}', 'show')
                ->middleware('permission:sale-return.view');

            Route::put('/{id}', 'update')
                ->middleware('permission:sale-return.update');

            Route::patch('/{id}/status', 'changeStatus')
                ->middleware('permission:sale-return.change-status');

            Route::delete('/{id}', 'destroy')
                ->middleware('permission:sale-return.delete');

            /*
            |--------------------------------------------------------------------------
            | Restore
            |--------------------------------------------------------------------------
            */

            Route::patch('/{id}/restore', 'restore')
                ->middleware('permission:sale-return.restore');

            Route::delete('/{id}/force-delete', 'forceDelete')
                ->middleware('permission:sale-return.force-delete');
        });


        Route::prefix('payment-modes')->controller(PaymentModeController::class)->group(function () {

            Route::get('/', 'index')->middleware('permission:payment-mode.list|payment-mode.view');

            Route::get('/active', 'active')->middleware('permission:payment-mode.list|payment-mode.view');

            Route::get('/trashed', 'trashed')->middleware('permission:payment-mode.view');

            Route::post('/', 'store')->middleware('permission:payment-mode.create');

            Route::get('/{id}', 'show')->middleware('permission:payment-mode.view');

            Route::put('/{id}', 'update')->middleware('permission:payment-mode.update');

            Route::delete('/{id}', 'destroy')->middleware('permission:payment-mode.delete');

            Route::patch('/{id}/restore', 'restore')->middleware('permission:payment-mode.restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:payment-mode.force-delete');
        });

        Route::prefix('payments')->controller(PaymentController::class)->group(function () {

            Route::get('/', 'index')->middleware('permission:payment.list|payment.view');

            Route::post('/', 'store')->middleware('permission:payment.create');

            Route::get('/trashed', 'trashed')->middleware('permission:payment.view');

            Route::get('/{id}', 'show')->middleware('permission:payment.view');

            Route::put('/{id}', 'update')->middleware('permission:payment.update');

            Route::delete('/{id}', 'destroy')->middleware('permission:payment.delete');

            Route::patch('/{id}/restore', 'restore')->middleware('permission:payment.restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:payment.force-delete');

            Route::patch('/{id}/change-status', 'changeStatus')->middleware('permission:payment.change-status');
        });

        Route::prefix('inventory')
            ->group(function () {

                /*
                |--------------------------------------------------------------------------
                | Stocks
                |--------------------------------------------------------------------------
                */

                Route::get('/stocks', [StockController::class, 'index'])
                    ->middleware('permission:stock.list|stock.view|stock-movement.list|stock-movement.view');
                Route::get('/stocks/{id}', [StockController::class, 'show'])
                    ->middleware('permission:stock.view|stock-movement.view');

                /*
                |--------------------------------------------------------------------------
                | Stock Movements
                |--------------------------------------------------------------------------
                */

                Route::get('/stock-movements', [StockMovementController::class, 'index'])
                    ->middleware('permission:stock-movement.list|stock-movement.view');
                Route::get('/stock-movements/{id}', [StockMovementController::class, 'show'])
                    ->middleware('permission:stock-movement.view');

                /*
                |--------------------------------------------------------------------------
                | Stock Adjustments
                |--------------------------------------------------------------------------
                */

                Route::apiResource(
                    'stock-adjustments',
                    StockAdjustmentController::class
                )->only(['index', 'store', 'show'])
                    ->middlewareFor('index', 'permission:stock-adjustment.list|stock-adjustment.view')
                    ->middlewareFor('show', 'permission:stock-adjustment.view')
                    ->middlewareFor('store', 'permission:stock-adjustment.create');
            });

        Route::prefix('pos')
            ->controller(POSController::class)
            ->group(function () {

                Route::get('dashboard', 'dashboard')->middleware('permission:cash-register-session.view');

                Route::get('session-context', 'sessionContext')->middleware('permission:cash-register-session.view');

                Route::get('registers', 'registers')->middleware('permission:cash-register.list|cash-register.view');

                Route::post('registers', 'storeRegister')->middleware('permission:cash-register.create');

                Route::get('registers/{id}', 'showRegister')->middleware('permission:cash-register.view');

                Route::put('registers/{id}', 'updateRegister')->middleware('permission:cash-register.update');

                Route::delete('registers/{id}', 'deleteRegister')->middleware('permission:cash-register.delete');

                Route::post('open-session', 'openSession')->middleware('permission:cash-register-session.create');

                Route::put('close-session/{id}', 'closeSession')->middleware('permission:cash-register-session.update');

                Route::post('checkout', 'checkout')->middleware('permission:sale-order.create');

                Route::get('orders/{id}', 'order')
                    ->middleware(['permission:sale-order.view', 'permission:cash-register-session.view']);

                Route::put('orders/{id}', 'updateOrder')
                    ->middleware(['permission:sale-order.update', 'permission:cash-register-session.view']);

                Route::post('cash-in', 'cashIn')->middleware('permission:cash-register-transaction.create');

                Route::post('cash-out', 'cashOut')->middleware('permission:cash-register-transaction.create');

                Route::get('summary/{id}', 'summary')->middleware('permission:cash-register-transaction.view');

                Route::get('barcode/{barcode}', 'barcode')
                    ->middleware(['permission:product.view', 'permission:cash-register-session.view']);

                Route::get('search', 'search')
                    ->middleware(['permission:product.view', 'permission:cash-register-session.view']);

                Route::get('holds', 'heldBills')->middleware('permission:cash-hold.list|cash-hold.view');

                Route::post('hold', 'hold')->middleware('permission:cash-hold.create');

                Route::put('hold/{id}', 'updateHold')->middleware('permission:cash-hold.update');

                Route::get('hold/{id}', 'recall')->middleware('permission:cash-hold.view');

                Route::patch('hold/{id}/cancel', 'cancel')->middleware('permission:cash-hold.delete');
            });

        Route::prefix('general-settings')
            ->controller(GeneralSettingController::class)
            ->group(function () {

                Route::get('/', 'show')->middleware('permission:general-setting.view');

                Route::put('/', 'update')->middleware('permission:general-setting.update');
            });

        Route::prefix('barcode-templates')
            ->controller(BarcodeTemplateController::class)
            ->group(function () {

                Route::get('/', 'index')->middleware('permission:barcode-template.list|barcode-template.view');

                Route::get('/active', 'active')->middleware('permission:barcode-template.list|barcode-template.view');

                Route::get('/{id}', 'show')->middleware('permission:barcode-template.view');

                Route::post('/', 'store')->middleware('permission:barcode-template.create');

                Route::put('/{id}', 'update')->middleware('permission:barcode-template.update');

                Route::delete('/{id}', 'destroy')->middleware('permission:barcode-template.delete');
            });

        Route::prefix('products')
            ->controller(ProductBarcodeController::class)
            ->group(function () {

                Route::get('{id}/barcode', 'show')->middleware('permission:barcode-print.view');

                Route::post('barcode/bulk', 'bulk')->middleware('permission:barcode-print.create');
            });

        Route::prefix('barcode')
            ->controller(BarcodePrintController::class)
            ->group(function () {

                Route::post('preview', 'preview')->middleware('permission:barcode-print.create');

                Route::post('pdf', 'pdf')->middleware('permission:barcode-print.create');
            });

        Route::prefix('website-banners')
            ->controller(WebsiteBannerController::class)
            ->group(function () {

                Route::get('/', 'index')->middleware('permission:website-banner.list|website-banner.view');
                Route::get('/active', 'active')->middleware('permission:website-banner.list|website-banner.view');
                Route::get('/trash', 'trash')->middleware('permission:website-banner.view');
                Route::get('/{id}', 'show')->middleware('permission:website-banner.view');

                Route::post('/', 'store')->middleware('permission:website-banner.create');

                Route::put('/{id}', 'update')->middleware('permission:website-banner.update');

                Route::patch('/{id}/status', 'changeStatus')->middleware('permission:website-banner.change-status');

                Route::delete('/{id}', 'destroy')->middleware('permission:website-banner.delete');

                Route::put('/{id}/restore', 'restore')->middleware('permission:website-banner.restore');

                Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:website-banner.force-delete');

                Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:website-banner.delete');
            });

        /*
|--------------------------------------------------------------------------
| Delivery Boys
|--------------------------------------------------------------------------
*/

        Route::prefix('delivery-boys')->group(function () {

            Route::get(
                '/unlinked-users',
                [DeliveryBoyController::class, 'unlinkedUsers']
            )->middleware('permission:delivery-boy.create');

            Route::get(
                '/',
                [DeliveryBoyController::class, 'index']
            )->middleware('permission:delivery-boy.list');

            Route::post(
                '/',
                [DeliveryBoyController::class, 'store']
            )->middleware('permission:delivery-boy.create');

            Route::get(
                '/{id}',
                [DeliveryBoyController::class, 'show']
            )->middleware('permission:delivery-boy.view');

            Route::put(
                '/{id}',
                [DeliveryBoyController::class, 'update']
            )->middleware('permission:delivery-boy.update');

            Route::delete(
                '/{id}',
                [DeliveryBoyController::class, 'destroy']
            )->middleware('permission:delivery-boy.delete');
        });

        /*
        |--------------------------------------------------------------------------
        | Delivery Assignments
        |--------------------------------------------------------------------------
        */

        Route::prefix('delivery-assignments')->group(function () {

            Route::get(
                '/',
                [DeliveryAssignmentController::class, 'index']
            )->middleware('permission:delivery-assignment.list');

            Route::post(
                '/assign',
                [DeliveryAssignmentController::class, 'assign']
            )->middleware('permission:delivery-assignment.create');

            Route::get(
                '/{id}',
                [DeliveryAssignmentController::class, 'show']
            )->middleware('permission:delivery-assignment.view');

            Route::delete(
                '/{id}',
                [DeliveryAssignmentController::class, 'destroy']
            )->middleware('permission:delivery-assignment.delete');

            Route::patch(
                '/{id}/accept',
                [DeliveryAssignmentController::class, 'accept']
            )->middleware('permission:delivery-assignment.update');

            Route::patch(
                '/{id}/reject',
                [DeliveryAssignmentController::class, 'reject']
            )->middleware('permission:delivery-assignment.update');

            Route::patch(
                '/{id}/pickup',
                [DeliveryAssignmentController::class, 'pickup']
            )->middleware('permission:delivery-assignment.update');

            Route::patch(
                '/{id}/out-for-delivery',
                [DeliveryAssignmentController::class, 'outForDelivery']
            )->middleware('permission:delivery-assignment.update');

            Route::patch(
                '/{id}/delivered',
                [DeliveryAssignmentController::class, 'delivered']
            )->middleware('permission:delivery-assignment.update');

            Route::get(
                '/history/{saleOrderId}',
                [DeliveryAssignmentController::class, 'history']
            )->middleware('permission:delivery-assignment.view');
        });

        Route::get(
            'delivery-confirmations',
            [AdminDeliveryConfirmationController::class, 'index']
        )->middleware('permission:delivery-confirmation.list');

        Route::get(
            'delivery-confirmations/{id}',
            [AdminDeliveryConfirmationController::class, 'show']
        )->middleware('permission:delivery-confirmation.view');

        Route::patch(
            'delivery-confirmations/{id}/resolve',
            [AdminDeliveryConfirmationController::class, 'resolve']
        )->middleware('permission:delivery-confirmation.resolve');
    });


    /*
    |--------------------------------------------------------------------------
    | Website Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('website')->group(function () {
        Route::get('/home', [HomeController::class, 'index']);

        Route::get(
            'products',
            [WebsiteProductController::class, 'index']
        );

        Route::get(
            'products/{slug}',
            [WebsiteProductController::class, 'show']
        );

        Route::get(
            'products/search/{keyword}',
            [WebsiteProductController::class, 'searchSuggestions']
        );

        Route::get(
            'products/{slug}/related',
            [WebsiteProductController::class, 'relatedProducts']
        );

        /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

        Route::controller(WebsiteCategoryController::class)

            ->prefix('categories')

            ->group(function () {

                Route::get(
                    '/tree',
                    'tree'
                );

                /**
                 * Category Listing
                 */
                Route::get(
                    '/',
                    'index'
                );

                /**
                 * Category Details
                 */
                Route::get(
                    '/{slug}',
                    'show'
                );
            });

        /*
|--------------------------------------------------------------------------
| Website Brands
|--------------------------------------------------------------------------
*/

        Route::prefix('brands')
            ->controller(WebsiteBrandController::class)
            ->group(function (): void {

                /**
                 * Brand Listing
                 * GET /api/brands
                 */
                Route::get(
                    '/',
                    'index'
                );

                /**
                 * Brand Details
                 * GET /api/brands/{slug}
                 */
                Route::get(
                    '/{slug}',
                    'show'
                );
            });

        /*
|--------------------------------------------------------------------------
| Website Units
|--------------------------------------------------------------------------
*/

        Route::prefix('units')
            ->controller(WebsiteUnitController::class)
            ->group(function (): void {

                /**
                 * Unit Listing
                 * GET /api/units
                 */
                Route::get(
                    '/',
                    'index'
                );

                /**
                 * Unit Details
                 * GET /api/units/{slug}
                 */
                Route::get(
                    '/{slug}',
                    'show'
                );
            });

        Route::middleware(['auth:sanctum', 'role:Customer'])->group(function () {

            Route::prefix('customer-addresses')
                ->controller(WebsiteCustomerAddressController::class)
                ->group(function () {
                    Route::get('/', 'index');
                    Route::post('/', 'store');
                    Route::get('/default', 'default');
                    Route::put('/{id}', 'update');
                    Route::delete('/{id}', 'destroy');
                    Route::patch('/{id}/default', 'setDefault');
                });

            Route::get(
                'wishlist',
                [WishlistController::class, 'index']
            );

            Route::post(
                'wishlist',
                [WishlistController::class, 'store']
            );

            Route::delete(
                'wishlist/{productId}',
                [WishlistController::class, 'destroy']
            );

            Route::delete(
                'wishlist',
                [WishlistController::class, 'clear']
            );

            Route::get(
                'wishlist/count',
                [WishlistController::class, 'count']
            );

            Route::get(
                'wishlist/check/{productId}',
                [WishlistController::class, 'check']
            );
        });

        Route::middleware(['auth:sanctum', 'role:Customer'])
            ->prefix('cart')
            ->controller(CartController::class)
            ->group(function () {

                Route::get('/', 'index');

                Route::post('/', 'store');

                Route::patch('/{productId}', 'update');

                Route::delete('/{productId}', 'destroy');

                Route::delete('/', 'clear');

                Route::get('/count', 'count');

                Route::post('/apply-coupon', 'applyCoupon');

                Route::delete('/remove-coupon', 'removeCoupon');
            });

        Route::middleware(['auth:sanctum', 'role:Customer'])
            ->prefix('checkout')
            ->controller(CheckoutController::class)
            ->group(function () {

                Route::post('/', 'checkout');

                Route::get('/orders', 'orders');

                Route::get('/orders/{saleNo}', 'orderDetails');

                Route::post('/orders/{saleNo}/cancel', 'cancelOrder');

                Route::post(
                    '/orders/{saleNo}/delivery/confirm',
                    [WebsiteDeliveryConfirmationController::class, 'confirm']
                );

                Route::post(
                    '/orders/{saleNo}/delivery/otp',
                    [WebsiteDeliveryConfirmationController::class, 'otp']
                );

                Route::post(
                    '/orders/{saleNo}/delivery/dispute',
                    [WebsiteDeliveryConfirmationController::class, 'dispute']
                );
            });

        Route::middleware(['auth:sanctum', 'role:Customer'])
            ->prefix('payment')
            ->controller(WebsitePaymentController::class)
            ->group(function () {

                Route::post(
                    '/verify',
                    'verifyPayment'
                );
            });
    });
});
