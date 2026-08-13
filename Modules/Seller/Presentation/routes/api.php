<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Seller\Presentation\Controllers\AdminSellerController;
use Modules\Seller\Presentation\Controllers\SellerProfileController;
use Modules\Seller\Presentation\Controllers\SellerOrderController;

Route::middleware(['auth:sanctum', 'role.seller'])->prefix('seller')->group(function (): void {
    Route::get('profile', [SellerProfileController::class, 'show']);
    Route::put('profile', [SellerProfileController::class, 'update']);
    Route::get('orders', [SellerOrderController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role.admin'])->prefix('admin/sellers')->group(function (): void {
    Route::get('/', [AdminSellerController::class, 'index']);
    Route::put('{seller}/verify', [AdminSellerController::class, 'verify']);
});
