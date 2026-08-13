<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Presentation\Controllers\ProductController;
use Modules\Product\Presentation\Controllers\ProductRevisionModerationController;
use Modules\Product\Presentation\Controllers\SellerProductController;

// Public
Route::prefix('products')->group(function (): void {
    Route::get('/',           [ProductController::class, 'index']);
    Route::get('/{product}',  [ProductController::class, 'show']);
});

// Manager — yaratish va tahrirlash
Route::middleware(['auth:sanctum', 'role.manager'])->prefix('products')->group(function (): void {
    Route::post('/',          [ProductController::class, 'store']);
    Route::put('/{product}',  [ProductController::class, 'update']);
});

// Admin — o'chirish
Route::middleware(['auth:sanctum', 'role.admin'])->prefix('products')->group(function (): void {
    Route::delete('/{product}', [ProductController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role.seller'])->prefix('seller')->group(function (): void {
    Route::post('pricing/preview', [SellerProductController::class, 'pricing']);
    Route::get('analytics', [SellerProductController::class, 'analytics']);
    Route::get('products', [SellerProductController::class, 'index']);
    Route::post('products', [SellerProductController::class, 'store'])->middleware('seller.verified');
    Route::post('products/{product}/revisions', [SellerProductController::class, 'update'])->middleware('seller.verified');
});

Route::middleware(['auth:sanctum', 'role.admin'])->prefix('admin/product-revisions')->group(function (): void {
    Route::get('/', [ProductRevisionModerationController::class, 'index']);
    Route::put('{revision}/approve', [ProductRevisionModerationController::class, 'approve']);
    Route::put('{revision}/reject', [ProductRevisionModerationController::class, 'reject']);
});
