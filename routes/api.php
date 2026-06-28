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

            Route::get('/{user}', 'show');              // User Details

            Route::put('/{user}', 'update');            // Update User

            Route::delete('/{user}', 'destroy');        // Delete User

            Route::patch('/{user}/status', 'changeStatus'); // Change Status

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

            Route::get('/', 'index');

            Route::post('/', 'store');

            Route::get('/{category}', 'show');

            Route::put('/{category}', 'update');

            Route::delete('/{category}', 'destroy');

            Route::patch('/{category}/status', 'changeStatus');

        });

});
