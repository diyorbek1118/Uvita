<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Seller\Presentation\Controllers\AdminSellerController;
use Modules\Seller\Presentation\Controllers\SellerAuthController;
use Modules\Seller\Presentation\Controllers\SellerOrderController;
use Modules\Seller\Presentation\Controllers\SellerProfileController;

Route::post('seller/login', [SellerAuthController::class, 'login'])
    ->middleware('throttle:staff-login');

Route::middleware(['auth:sanctum', 'role.seller'])->prefix('seller')->group(function (): void {
    Route::get('profile', [SellerProfileController::class, 'show']);
    Route::put('profile', [SellerProfileController::class, 'update']);
    Route::get('orders', [SellerOrderController::class, 'index']);
    Route::get('shops', [SellerProfileController::class, 'shops']);
});

Route::middleware(['auth:sanctum', 'role.admin'])->prefix('admin/sellers')->group(function (): void {
    Route::get('accounts', [AdminSellerController::class, 'sellers']);
    Route::post('accounts', [AdminSellerController::class, 'store']);
    Route::post('{seller}/shops', [AdminSellerController::class, 'addShop']);
    Route::get('/', [AdminSellerController::class, 'index']);
    Route::put('{seller}/verify', [AdminSellerController::class, 'verify']);
});

Route::middleware(['auth:sanctum', 'role.admin'])->put(
    'admin/seller-shops/{shop}/verify',
    [AdminSellerController::class, 'verifyShop']
);
