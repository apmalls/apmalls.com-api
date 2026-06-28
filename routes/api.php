<?php


use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Product\CategoryController;
use App\Http\Controllers\Api\V1\User\UserController;
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

});
