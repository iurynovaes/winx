<?php

use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterUserController;
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
});
