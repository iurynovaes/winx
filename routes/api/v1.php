<?php

use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterUserController;
use App\Http\Controllers\Api\V1\Catalog\DestroyProductController;
use App\Http\Controllers\Api\V1\Catalog\IndexCategoryController;
use App\Http\Controllers\Api\V1\Catalog\ShowCategoryController;
use App\Http\Controllers\Api\V1\Catalog\ShowProductController;
use App\Http\Controllers\Api\V1\Catalog\StoreCategoryController;
use App\Http\Controllers\Api\V1\Catalog\StoreProductController;
use App\Http\Controllers\Api\V1\Catalog\UpdateCategoryController;
use App\Http\Controllers\Api\V1\Catalog\UpdateProductController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/register', RegisterUserController::class)
        ->middleware('throttle:login')
        ->name('register');

    Route::post('/login', LoginController::class)
        ->middleware('throttle:login')
        ->name('login');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', CurrentUserController::class)->name('auth.me');
    Route::post('/auth/logout', LogoutController::class)->name('auth.logout');

    Route::get('/categories', IndexCategoryController::class)->name('categories.index');
    Route::post('/categories', StoreCategoryController::class)->name('categories.store');
    Route::get('/categories/{category}', ShowCategoryController::class)->name('categories.show');
    Route::match(['put', 'patch'], '/categories/{category}', UpdateCategoryController::class)->name('categories.update');

    Route::post('/products', StoreProductController::class)->name('products.store');
    Route::get('/products/{product}', ShowProductController::class)->name('products.show');
    Route::match(['put', 'patch'], '/products/{product}', UpdateProductController::class)->name('products.update');
    Route::delete('/products/{product}', DestroyProductController::class)->name('products.destroy');
});
