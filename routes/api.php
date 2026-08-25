<?php


use App\Http\Controllers\Api\V1\Admin\Banner\WebsiteBannerController;
use App\Http\Controllers\Api\V1\Admin\Barcode\BarcodePrintController;
use App\Http\Controllers\Api\V1\Admin\Delivery\DeliveryAssignmentController;
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
use App\Http\Controllers\Api\V1\Website\HomeController;
use App\Http\Controllers\Api\V1\Website\ProductController as WebsiteProductController;
use App\Http\Controllers\Api\V1\Website\CustomerAddressController as WebsiteCustomerAddressController;
use App\Http\Controllers\Api\V1\Website\SaleOrderController as WebsiteSaleOrderController;
use App\Http\Controllers\Api\V1\Website\WishlistController;
use App\Http\Controllers\Api\V1\Website\PaymentController as WebsitePaymentController;
use App\Http\Controllers\Api\V1\Website\CheckoutController;
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

    Route::middleware('auth:sanctum')
        ->prefix('permissions')
        ->group(function () {

            Route::get('/', [PermissionController::class, 'index'])
                ->middleware('permission:permission.view');

            Route::get('/grouped', [PermissionController::class, 'grouped'])
                ->middleware('permission:permission.view');

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

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/{id}', 'show');

            Route::put('/{id}', 'update');

            Route::delete('/{id}', 'destroy');
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

            Route::get('/', 'index');                   // User List

            Route::post('/', 'store');                  // Create User

            Route::get('/{id}', 'show');              // User Details

            Route::put('/{id}', 'update');            // Update User

            Route::delete('/{id}', 'destroy');        // Delete User

            Route::patch('/{id}/status', 'changeStatus'); // Change Status

            Route::get('/trash', 'trash');

            Route::put('/{id}/restore', 'restore');

            Route::delete('/{id}/force-delete', 'forceDelete');
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
            Route::get('/', 'index');                        // Brand List
            Route::get('/dropdown', 'dropdown');             // Brand Dropdown
            Route::get('/trash', 'trash');                   // Trashed Brands
            Route::post('/', 'store');                       // Create Brand
            Route::get('/{id}', 'show');                     // Brand Details
            Route::put('/{id}', 'update');                   // Update Brand
            Route::delete('/{id}', 'destroy');               // Delete Brand
            Route::patch('/{id}/status', 'changeStatus');    // Change Status
            Route::put('/{id}/restore', 'restore');          // Restore Brand
            Route::delete('/{id}/force-delete', 'forceDelete'); // Force Delete
            Route::post('/bulk-delete', 'bulkDelete');       // Bulk Delete
            Route::patch('/bulk-status', 'bulkStatusUpdate'); // Bulk Status Update
        });

        Route::prefix('units')->controller(UnitController::class)->group(function () {

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/trash', 'trash');

            Route::get('/{id}', 'show');

            Route::put('/{id}', 'update');

            Route::delete('/{id}', 'destroy');

            Route::patch('/{id}/status', 'changeStatus');

            Route::put('/{id}/restore', 'restore');

            Route::delete('/{id}/force-delete', 'forceDelete');
        });


        Route::prefix('products')->controller(ProductController::class)->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Product CRUD
            |--------------------------------------------------------------------------
            */

            Route::get('/', 'index');                    // Product List

            Route::post('/', 'store');                   // Create Product

            Route::get('/trash', 'trash');               // Trash List

            Route::get('/{id}', 'show');                 // Product Details

            Route::put('/{id}', 'update');               // Update Product

            Route::delete('/{id}', 'destroy');           // Soft Delete

            Route::patch('/{id}/status', 'changeStatus'); // Change Status

            Route::put('/{id}/restore', 'restore');      // Restore Product

            Route::delete('/{id}/force-delete', 'forceDelete'); // Permanent Delete

            Route::controller(ProductImageController::class)->group(function () {

                /*
                |--------------------------------------------------------------------------
                | Product Gallery
                |--------------------------------------------------------------------------
                */

                Route::get('/{product}/images', 'index');

                Route::post('/{product}/images', 'store');

                Route::put('/images/{image}', 'update');

                Route::delete('/images/{image}', 'destroy');

                Route::patch('/images/{image}/sort-order', 'updateSortOrder');
            });
        });

        Route::prefix('suppliers')->controller(SupplierController::class)->group(function () {

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/trash', 'trash');

            Route::get('/{id}', 'show');

            Route::put('/{id}', 'update');

            Route::delete('/{id}', 'destroy');

            Route::patch('/{id}/status', 'changeStatus');

            Route::put('/{id}/restore', 'restore');

            Route::delete('/{id}/force-delete', 'forceDelete');

            Route::controller(SupplierAddressController::class)->group(function () {

                Route::get('{supplier}/addresses', 'index');

                Route::post('{supplier}/addresses', 'store');

                Route::get('addresses/trash/{supplier}', 'trash');

                Route::get('addresses/{id}', 'show');

                Route::put('addresses/{id}', 'update');

                Route::delete('addresses/{id}', 'destroy');

                Route::patch('addresses/{id}/default', 'changeDefault');

                Route::put('addresses/{id}/restore', 'restore');

                Route::delete('addresses/{id}/force-delete', 'forceDelete');
            });
        });

        Route::prefix('customers')->controller(CustomerController::class)->group(function () {

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/trash', 'trash');

            Route::get('/{id}', 'show');

            Route::put('/{id}', 'update');

            Route::delete('/{id}', 'destroy');

            Route::patch('/{id}/status', 'changeStatus');

            Route::put('/{id}/restore', 'restore');

            Route::delete('/{id}/force-delete', 'forceDelete');

            Route::controller(CustomerAddressController::class)->group(function () {

                Route::get('{customer}/addresses', 'index');

                Route::post('{customer}/addresses', 'store');

                Route::get('addresses/trash/{customer}', 'trash');

                Route::get('addresses/{id}', 'show');

                Route::put('addresses/{id}', 'update');

                Route::delete('addresses/{id}', 'destroy');

                Route::patch('addresses/{id}/default', 'changeDefault');

                Route::put('addresses/{id}/restore', 'restore');

                Route::delete('addresses/{id}/force-delete', 'forceDelete');
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

            Route::get('/trashed', 'trashed')->middleware('permission:purchase-order.trash');

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

            Route::patch('/status/{id}', 'changeStatus')->middleware('permission:purchase-order.status');
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

            Route::patch('/{id}/status', 'changeStatus')->middleware('permission:sale-order.status');

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
                    ->middleware('permission:purchase-return-list');

                Route::get('/trash', 'trash')
                    ->middleware('permission:purchase-return-trash');

                Route::get('/count', 'count')
                    ->middleware('permission:purchase-return-list');

                Route::get('/total-amount', 'totalAmount')
                    ->middleware('permission:purchase-return-list');

                /*
                |--------------------------------------------------------------------------
                | CRUD
                |--------------------------------------------------------------------------
                */

                Route::post('/', 'store')
                    ->middleware('permission:purchase-return-create');

                Route::get('/{id}', 'show')
                    ->middleware('permission:purchase-return-view');

                Route::put('/{id}', 'update')
                    ->middleware('permission:purchase-return-update');

                Route::patch('/{id}/status', 'changeStatus')
                    ->middleware('permission:purchase-return-status');

                Route::delete('/{id}', 'destroy')
                    ->middleware('permission:purchase-return-delete');

                /*
                |--------------------------------------------------------------------------
                | Restore
                |--------------------------------------------------------------------------
                */

                Route::patch('/{id}/restore', 'restore')
                    ->middleware('permission:purchase-return-restore');

                Route::delete('/{id}/force-delete', 'forceDelete')
                    ->middleware('permission:purchase-return-force-delete');
            });


        Route::prefix('sale-returns')->controller(SaleReturnController::class)->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Listing
            |--------------------------------------------------------------------------
            */

            Route::get('/', 'index')
                ->middleware('permission:sale-return-list');

            Route::get('/trash', 'trash')
                ->middleware('permission:sale-return-trash');

            Route::get('/count', 'count')
                ->middleware('permission:sale-return-list');

            Route::get('/total-amount', 'totalAmount')
                ->middleware('permission:sale-return-list');

            /*
            |--------------------------------------------------------------------------
            | CRUD
            |--------------------------------------------------------------------------
            */

            Route::post('/', 'store')
                ->middleware('permission:sale-return-create');

            Route::get('/{id}', 'show')
                ->middleware('permission:sale-return-view');

            Route::put('/{id}', 'update')
                ->middleware('permission:sale-return-update');

            Route::patch('/{id}/status', 'changeStatus')
                ->middleware('permission:sale-return-status');

            Route::delete('/{id}', 'destroy')
                ->middleware('permission:sale-return-delete');

            /*
            |--------------------------------------------------------------------------
            | Restore
            |--------------------------------------------------------------------------
            */

            Route::patch('/{id}/restore', 'restore')
                ->middleware('permission:sale-return-restore');

            Route::delete('/{id}/force-delete', 'forceDelete')
                ->middleware('permission:sale-return-force-delete');
        });


        Route::prefix('payment-modes')->controller(PaymentModeController::class)->group(function () {

            Route::get('/', 'index')->middleware('permission:payment-mode-list');

            Route::get('/active', 'active')->middleware('permission:payment-mode-list');

            Route::get('/trashed', 'trashed')->middleware('permission:payment-mode-list');

            Route::post('/', 'store')->middleware('permission:payment-mode-create');

            Route::get('/{id}', 'show')->middleware('permission:payment-mode-view');

            Route::put('/{id}', 'update')->middleware('permission:payment-mode-update');

            Route::delete('/{id}', 'destroy')->middleware('permission:payment-mode-delete');

            Route::patch('/{id}/restore', 'restore')->middleware('permission:payment-mode-restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:payment-mode-force-delete');
        });

        Route::prefix('payments')->controller(PaymentController::class)->group(function () {

            Route::get('/', 'index')->middleware('permission:payment-list');

            Route::post('/', 'store')->middleware('permission:payment-create');

            Route::get('/trashed', 'trashed')->middleware('permission:payment-list');

            Route::get('/{id}', 'show')->middleware('permission:payment-view');

            Route::put('/{id}', 'update')->middleware('permission:payment-update');

            Route::delete('/{id}', 'destroy')->middleware('permission:payment-delete');

            Route::patch('/{id}/restore', 'restore')->middleware('permission:payment-restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:payment-force-delete');

            Route::patch('/{id}/change-status', 'changeStatus')->middleware('permission:payment-change-status');
        });

        Route::prefix('inventory')
            ->group(function () {

                /*
                |--------------------------------------------------------------------------
                | Stocks
                |--------------------------------------------------------------------------
                */

                Route::get('/stocks', [StockController::class, 'index']);
                Route::get('/stocks/{id}', [StockController::class, 'show']);

                /*
                |--------------------------------------------------------------------------
                | Stock Movements
                |--------------------------------------------------------------------------
                */

                Route::get('/stock-movements', [StockMovementController::class, 'index']);
                Route::get('/stock-movements/{id}', [StockMovementController::class, 'show']);

                /*
                |--------------------------------------------------------------------------
                | Stock Adjustments
                |--------------------------------------------------------------------------
                */

                Route::apiResource(
                    'stock-adjustments',
                    StockAdjustmentController::class
                );
            });

        Route::prefix('pos')
            ->controller(POSController::class)
            ->group(function () {

                Route::get('dashboard', 'dashboard');

                Route::get('session-context', 'sessionContext');

                Route::get('registers', 'registers');

                Route::post('registers', 'storeRegister');

                Route::get('registers/{id}', 'showRegister');

                Route::put('registers/{id}', 'updateRegister');

                Route::delete('registers/{id}', 'deleteRegister');

                Route::post('open-session', 'openSession');

                Route::put('close-session/{id}', 'closeSession');

                Route::post('checkout', 'checkout');

                Route::get('orders/{id}', 'order');

                Route::put('orders/{id}', 'updateOrder');

                Route::post('cash-in', 'cashIn');

                Route::post('cash-out', 'cashOut');

                Route::get('summary/{id}', 'summary');

                Route::get('barcode/{barcode}', 'barcode');

                Route::get('search', 'search');

                Route::get('holds', 'heldBills');

                Route::post('hold', 'hold');

                Route::put('hold/{id}', 'updateHold');

                Route::get('hold/{id}', 'recall');

                Route::patch('hold/{id}/cancel', 'cancel');
            });

        Route::prefix('general-settings')
            ->controller(GeneralSettingController::class)
            ->group(function () {

                Route::get('/', 'show');

                Route::put('/', 'update');
            });

        Route::prefix('barcode-templates')
            ->controller(BarcodeTemplateController::class)
            ->group(function () {

                Route::get('/', 'index');

                Route::get('/active', 'active');

                Route::get('/{id}', 'show');

                Route::post('/', 'store');

                Route::put('/{id}', 'update');

                Route::delete('/{id}', 'destroy');
            });

        Route::prefix('products')
            ->controller(ProductBarcodeController::class)
            ->group(function () {

                Route::get('{id}/barcode', 'show');

                Route::post('barcode/bulk', 'bulk');
            });

        Route::prefix('barcode')
            ->controller(BarcodePrintController::class)
            ->group(function () {

                Route::post('preview', 'preview');

                Route::post('pdf', 'pdf');
            });

        Route::prefix('website-banners')
            ->controller(WebsiteBannerController::class)
            ->group(function () {

                Route::get('/', 'index');
                Route::get('/active', 'active');
                Route::get('/trash', 'trash');
                Route::get('/{id}', 'show');

                Route::post('/', 'store');

                Route::put('/{id}', 'update');

                Route::patch('/{id}/status', 'changeStatus');

                Route::delete('/{id}', 'destroy');

                Route::put('/{id}/restore', 'restore');

                Route::delete('/{id}/force-delete', 'forceDelete');

                Route::post('/bulk-delete', 'bulkDelete');
            });

        /*
|--------------------------------------------------------------------------
| Delivery Boys
|--------------------------------------------------------------------------
*/

        Route::prefix('delivery-boys')->group(function () {

            Route::get(
                '/',
                [DeliveryBoyController::class, 'index']
            )->middleware('permission:delivery-boy-list');

            Route::post(
                '/',
                [DeliveryBoyController::class, 'store']
            )->middleware('permission:delivery-boy-create');

            Route::get(
                '/{id}',
                [DeliveryBoyController::class, 'show']
            )->middleware('permission:delivery-boy-view');

            Route::put(
                '/{id}',
                [DeliveryBoyController::class, 'update']
            )->middleware('permission:delivery-boy-edit');

            Route::delete(
                '/{id}',
                [DeliveryBoyController::class, 'destroy']
            )->middleware('permission:delivery-boy-delete');
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
            )->middleware('permission:delivery-assignment-list');

            Route::post(
                '/assign',
                [DeliveryAssignmentController::class, 'assign']
            )->middleware('permission:delivery-assignment-create');

            Route::get(
                '/{id}',
                [DeliveryAssignmentController::class, 'show']
            )->middleware('permission:delivery-assignment-view');

            Route::delete(
                '/{id}',
                [DeliveryAssignmentController::class, 'destroy']
            )->middleware('permission:delivery-assignment-delete');

            Route::patch(
                '/{id}/accept',
                [DeliveryAssignmentController::class, 'accept']
            )->middleware('permission:delivery-assignment-update');

            Route::patch(
                '/{id}/reject',
                [DeliveryAssignmentController::class, 'reject']
            )->middleware('permission:delivery-assignment-update');

            Route::patch(
                '/{id}/pickup',
                [DeliveryAssignmentController::class, 'pickup']
            )->middleware('permission:delivery-assignment-update');

            Route::patch(
                '/{id}/out-for-delivery',
                [DeliveryAssignmentController::class, 'outForDelivery']
            )->middleware('permission:delivery-assignment-update');

            Route::patch(
                '/{id}/delivered',
                [DeliveryAssignmentController::class, 'delivered']
            )->middleware('permission:delivery-assignment-update');

            Route::get(
                '/history/{saleOrderId}',
                [DeliveryAssignmentController::class, 'history']
            )->middleware('permission:delivery-assignment-view');
        });
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
            [ProductController::class, 'index']
        );

        Route::get(
            'products/{slug}',
            [ProductController::class, 'show']
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

        Route::controller(CategoryController::class)

            ->prefix('categories')

            ->group(function () {

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
            ->controller(BrandController::class)
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
            ->controller(UnitController::class)
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
