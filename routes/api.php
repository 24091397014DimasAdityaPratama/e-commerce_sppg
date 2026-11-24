<?php

use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/payments/midtrans/notification', [MidtransNotificationController::class, 'handle'])
        ->name('midtrans.notification');

    Route::post('/auth/register', [ApiAuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/auth/login', [ApiAuthController::class, 'login'])->middleware('throttle:6,1');

    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product:slug}', [ProductController::class, 'show']);
    Route::get('/suppliers', [ProductController::class, 'suppliers']);

    Route::middleware(['jwt.auth'])->group(function () {
        Route::get('/auth/me', [ApiAuthController::class, 'me']);
        Route::post('/auth/logout', [ApiAuthController::class, 'logout']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store'])->middleware('role:buyer');
        Route::post('/products', [ProductController::class, 'store'])->middleware('role:supplier');
        Route::get('/products/my', [ProductController::class, 'myProducts'])->middleware('role:supplier');
    });

    Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard']);
    });
});
