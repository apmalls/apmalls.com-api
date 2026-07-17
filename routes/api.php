<?php


use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Customer\CustomerController;

use App\Http\Controllers\Api\V1\Customer\CustomerAddressController;
use App\Http\Controllers\Api\V1\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Payment\PaymentModeController;
use App\Http\Controllers\Api\V1\Product\BrandController;
use App\Http\Controllers\Api\V1\Product\CategoryController;
use App\Http\Controllers\Api\V1\Product\ProductController;
use App\Http\Controllers\Api\V1\Product\ProductImageController;
use App\Http\Controllers\Api\V1\Product\UnitController;
use App\Http\Controllers\Api\V1\Purchase\PurchaseOrderController;
use App\Http\Controllers\Api\V1\Purchase\PurchaseReturnController;
use App\Http\Controllers\Api\V1\Sale\SaleOrderController;
use App\Http\Controllers\Api\V1\Sale\SaleReturnController;
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


    /*
|--------------------------------------------------------------------------
| User Management
|--------------------------------------------------------------------------
*/

    Route::middleware('auth:sanctum')
        ->prefix('users')
        ->controller(UserController::class)
        ->group(function () {

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
    Route::middleware('auth:sanctum')
        ->prefix('categories')
        ->controller(CategoryController::class)
        ->group(function () {
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


    Route::middleware('auth:sanctum')
        ->prefix('brands')
        ->controller(BrandController::class)
        ->group(function () {
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


    Route::middleware('auth:sanctum')->prefix('units')
        ->controller(UnitController::class)
        ->group(function () {

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


    Route::middleware('auth:sanctum')
        ->prefix('products')
        ->controller(ProductController::class)
        ->group(function () {

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


        });

    Route::middleware('auth:sanctum')
        ->prefix('products')
        ->controller(ProductImageController::class)
        ->group(function () {

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


    Route::middleware('auth:sanctum')
        ->prefix('suppliers')
        ->controller(SupplierController::class)
        ->group(function () {

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

    Route::middleware('auth:sanctum')
        ->prefix('suppliers')->controller(SupplierAddressController::class)
        ->group(function () {

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


    Route::middleware('auth:sanctum')
        ->prefix('customers')
        ->controller(CustomerController::class)
        ->group(function () {

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

    Route::middleware('auth:sanctum')
        ->prefix('customers')->controller(CustomerAddressController::class)
        ->group(function () {

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


    Route::prefix('purchase-orders')
        ->controller(PurchaseOrderController::class)
        ->group(function () {

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/trash', 'trash');

            Route::get('/{id}', 'show');

            Route::put('/{id}', 'update');

            Route::patch('/{id}/status', 'changeStatus');

            Route::delete('/{id}', 'destroy');

            Route::put('/{id}/restore', 'restore');

            Route::delete('/{id}/force-delete', 'forceDelete');

        });


    Route::prefix('sale-orders')
        ->controller(SaleOrderController::class)
        ->group(function () {

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/trash', 'trash');

            Route::get('/{id}', 'show');

            Route::put('/{id}', 'update');

            Route::patch('/{id}/status', 'changeStatus');

            Route::delete('/{id}', 'destroy');

            Route::put('/{id}/restore', 'restore');

            Route::delete('/{id}/force-delete', 'forceDelete');

        });


    Route::prefix('purchase-returns')
        ->controller(PurchaseReturnController::class)
        ->group(function () {

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/trash', 'trash');

            Route::get('/{id}', 'show');

            Route::put('/{id}', 'update');

            Route::patch('/{id}/status', 'changeStatus');

            Route::delete('/{id}', 'destroy');

            Route::put('/{id}/restore', 'restore');

            Route::delete('/{id}/force-delete', 'forceDelete');

        });


    Route::prefix('sale-returns')
        ->controller(SaleReturnController::class)
        ->group(function () {

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/trash', 'trash');

            Route::get('/{id}', 'show');

            Route::put('/{id}', 'update');

            Route::patch('/{id}/status', 'changeStatus');

            Route::delete('/{id}', 'destroy');

            Route::put('/{id}/restore', 'restore');

            Route::delete('/{id}/force-delete', 'forceDelete');

        });


    Route::prefix('payment-modes')
        ->controller(PaymentModeController::class)
        ->group(function () {

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/{id}', 'show');

            Route::put('/{id}', 'update');

            Route::delete('/{id}', 'destroy');

        });


    Route::prefix('payments')
        ->controller(PaymentController::class)
        ->group(function () {

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/{id}', 'show');

            Route::put('/{id}', 'update');

            Route::patch('/{id}/status', 'changeStatus');

            Route::delete('/{id}', 'destroy');

            Route::get('/trash', 'trash');

            Route::put('/{id}/restore', 'restore');

            Route::delete('/{id}/force-delete', 'forceDelete');

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

                });

        });


});
