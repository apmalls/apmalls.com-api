<?php


use App\Http\Controllers\API\POS\POSController;
use App\Http\Controllers\Api\V1\Admin\Purchase\PurchaseReturnController;
use App\Http\Controllers\Api\V1\Admin\Sale\SaleController;
use App\Http\Controllers\Api\V1\Admin\Sale\SaleReturnController;
use App\Http\Controllers\Api\V1\Admin\Inventory\StockAdjustmentController;
use App\Http\Controllers\Api\V1\Admin\Inventory\StockController;
use App\Http\Controllers\Api\V1\Admin\Inventory\StockMovementController;
use App\Http\Controllers\Api\V1\Admin\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Admin\Payment\PaymentModeController;
use App\Http\Controllers\Api\V1\Admin\Permission\PermissionController;
use App\Http\Controllers\Api\V1\Admin\PurchaseOrderController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Customer\CustomerController;

use App\Http\Controllers\Api\V1\Customer\CustomerAddressController;

use App\Http\Controllers\Api\V1\Dashboard\DashboardController;


use App\Http\Controllers\Api\V1\Product\BrandController;
use App\Http\Controllers\Api\V1\Product\CategoryController;
use App\Http\Controllers\Api\V1\Product\ProductController;
use App\Http\Controllers\Api\V1\Product\ProductImageController;
use App\Http\Controllers\Api\V1\Product\UnitController;


use App\Http\Controllers\Api\V1\Role\RoleController;


use App\Http\Controllers\Api\V1\Supplier\SupplierAddressController;
use App\Http\Controllers\Api\V1\Supplier\SupplierController;
use App\Http\Controllers\Api\V1\User\UserController;

use App\Http\Controllers\Api\V1\Website\CartController;
use App\Http\Controllers\Api\V1\Website\CategoryController as WebsiteCategoryController;
use App\Http\Controllers\Api\V1\Website\HomeController;
use App\Http\Controllers\Api\V1\Website\ProductController as WebsiteProductController;
use App\Http\Controllers\Api\V1\Website\CustomerAddressController as WebsiteCustomerAddressController;
use App\Http\Controllers\Api\V1\Website\SaleOrderController as WebsiteSaleOrderController;
use App\Http\Controllers\Api\V1\Website\WishlistController;
use App\Http\Controllers\Api\V1\Website\PaymentController as WebsitePaymentController;
use Illuminate\Support\Facades\Route;


// Test Route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

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
            Route::get('/', 'index');                    // Category List
            Route::post('/', 'store');                   // Create Category
            Route::get('/tree', 'tree');                 // Category Tree
            Route::get('/trash', 'trash');               // Trashed Categories
            Route::get('/{id}', 'show');                 // Category Details
            Route::put('/{id}', 'update');               // Update Category
            Route::delete('/{id}', 'destroy');           // Delete Category
            Route::patch('/{id}/status', 'changeStatus'); // Change Status
            Route::put('/{id}/restore', 'restore');      // Restore Category
            Route::delete('/{id}/force-delete', 'forceDelete'); // Force Delete
            Route::post('/bulk-delete', 'bulkDelete');   // Bulk Delete
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

            Route::patch('/{id}/status', 'changeStatus');// Change Status

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

            Route::put('/{id}', 'update')->middleware('permission:purchase-order.edit');

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

            Route::get('/count', 'count')->middleware('permission:sale.view');

            Route::get('/total-amount', 'totalAmount')->middleware('permission:sale.view');

            /*
            |--------------------------------------------------------------------------
            | Trash
            |--------------------------------------------------------------------------
            */

            Route::get('/trash', 'trash')->middleware('permission:sale.view');

            Route::patch('/{id}/restore', 'restore')->middleware('permission:sale.restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:sale.force-delete');

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            Route::patch('/{id}/status', 'changeStatus')->middleware('permission:sale.status');

            /*
            |--------------------------------------------------------------------------
            | CRUD
            |--------------------------------------------------------------------------
            */

            Route::get('/', 'index')->middleware('permission:sale.view');

            Route::post('/', 'store')->middleware('permission:sale.create');

            Route::get('/{id}', 'show')->middleware('permission:sale.view');

            Route::put('/{id}', 'update')->middleware('permission:sale.edit');

            Route::delete('/{id}', 'destroy')->middleware('permission:sale.delete');
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
                    ->middleware('permission:purchase-return-edit');

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
                ->middleware('permission:sale-return-edit');

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

            Route::put('/{id}', 'update')->middleware('permission:payment-mode-edit');

            Route::delete('/{id}', 'destroy')->middleware('permission:payment-mode-delete');

            Route::patch('/{id}/restore', 'restore')->middleware('permission:payment-mode-restore');

            Route::delete('/{id}/force-delete', 'forceDelete')->middleware('permission:payment-mode-force-delete');

        });

        Route::prefix('payments')->controller(PaymentController::class)->group(function () {

            Route::get('/', 'index')->middleware('permission:payment-list');

            Route::post('/', 'store')->middleware('permission:payment-create');

            Route::get('/trashed', 'trashed')->middleware('permission:payment-list');

            Route::get('/{id}', 'show')->middleware('permission:payment-view');

            Route::put('/{id}', 'update')->middleware('permission:payment-edit');

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

                Route::post('open-session', 'openSession');

                Route::put('close-session/{id}', 'closeSession');

                Route::post('checkout', 'checkout');

                Route::post('cash-in', 'cashIn');

                Route::post('cash-out', 'cashOut');

                Route::get('summary/{id}', 'summary');

                Route::get('barcode/{barcode}', 'barcode');

                Route::get('search', 'search');

                Route::post('hold', 'hold');

                Route::put('hold/{id}', 'updateHold');

                Route::get('hold/{id}', 'recall');

                Route::patch('hold/{id}/cancel', 'cancel');
            });

    });


    /*
    |--------------------------------------------------------------------------
    | Website Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('website')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Categories
            |--------------------------------------------------------------------------
            */

            Route::controller(WebsiteCategoryController::class)

                ->prefix('categories')

                ->group(function () {

                    /*
                    |--------------------------------------------------------------------------
                    | Category Listing
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/',
                        'index'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Featured Categories
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/featured',
                        'featured'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Category Details
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/{slug}',
                        'show'
                    );

                });

            /*
   |--------------------------------------------------------------------------
   | Products
   |--------------------------------------------------------------------------
   */

            Route::controller(WebsiteProductController::class)
                ->prefix('products')
                ->group(function () {

                    /*
                    |--------------------------------------------------------------------------
                    | Product Listing
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/',
                        'index'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Product Search
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/search',
                        'search'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Featured Products
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/featured',
                        'featured'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | New Arrival Products
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/new-arrivals',
                        'newArrivals'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Best Seller Products
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/best-sellers',
                        'bestSellers'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Product Details
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/{slug}',
                        'show'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Related Products
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/{slug}/related',
                        'related'
                    );

                });

            /*
    |--------------------------------------------------------------------------
    | Home
    |--------------------------------------------------------------------------
    */

            Route::controller(HomeController::class)
                ->group(function () {

                    Route::get(
                        'home',
                        'index'
                    );

                });


            /*
    |--------------------------------------------------------------------------
    | Cart
    |--------------------------------------------------------------------------
    */

            Route::controller(CartController::class)->middleware('auth:sanctum')
                ->prefix('cart')
                ->group(function () {

                    /*
       |--------------------------------------------------------------------------
       | Cart
       |--------------------------------------------------------------------------
       */

                    Route::get(
                        '/',
                        'index'
                    );

                    Route::get(
                        '/summary',
                        'summary'
                    );

                    Route::post(
                        '/items',
                        'store'
                    );

                    Route::put(
                        '/items/{item}',
                        'update'
                    );

                    Route::delete(
                        '/items/{item}',
                        'destroy'
                    );

                    Route::delete(
                        '/clear',
                        'clear'
                    );
                });


            Route::middleware('auth:sanctum')
                ->prefix('wishlist')
                ->controller(WishlistController::class)
                ->group(function () {

                    /*
                    |--------------------------------------------------------------------------
                    | Wishlist
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/',
                        'index'
                    );

                    Route::get(
                        '/count',
                        'count'
                    );

                    Route::post(
                        '/',
                        'store'
                    );

                    Route::delete(
                        '/{wishlist}',
                        'destroy'
                    );

                    Route::delete(
                        '/clear',
                        'clear'
                    );

                });


            Route::middleware('auth:sanctum')
                ->prefix('customer-addresses')
                ->controller(WebsiteCustomerAddressController::class)
                ->group(function () {

                    Route::get(
                        '/',
                        'index'
                    );

                    Route::get(
                        '/default',
                        'default'
                    );

                    Route::post(
                        '/',
                        'store'
                    );

                    Route::put(
                        '/{id}',
                        'update'
                    );

                    Route::patch(
                        '/{id}/default',
                        'setDefault'
                    );

                    Route::delete(
                        '/{id}',
                        'destroy'
                    );

                });

            Route::middleware('auth:sanctum')
                ->prefix('orders')
                ->controller(WebsiteSaleOrderController::class)
                ->group(function () {

                    Route::get(
                        '/',
                        'index'
                    );

                    Route::get(
                        '/{id}',
                        'show'
                    );

                    Route::post(
                        '/place-order',
                        'placeOrder'
                    );

                    Route::patch(
                        '/{id}/cancel',
                        'cancel'
                    );

                    Route::get('/{id}/invoice', 'downloadInvoice');

                });

            Route::middleware('auth:sanctum')
                ->prefix('payments')
                ->controller(WebsitePaymentController::class)
                ->group(function () {

                    Route::get(
                        '/modes',
                        'paymentModes'
                    );

                    Route::post(
                        '/{orderId}',
                        'pay'
                    );

                    Route::post(
                        '/razorpay/create-order',
                        'createRazorpayOrder'
                    );

                    Route::post(
                        '/razorpay/verify',
                        'verifyRazorpayPayment'
                    );

                    Route::post(
                        '/razorpay/webhook',
                        'razorpayWebhook'
                    );

                });

        });


});
