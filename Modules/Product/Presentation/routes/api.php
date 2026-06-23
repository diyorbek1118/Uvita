<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Presentation\Controllers\ProductController;

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

// Admin — tasdiqlash va rad etish (/admin/ prefix bilan)
Route::middleware(['auth:sanctum', 'role.admin'])->prefix('admin')->group(function (): void {
    Route::put('products/{id}/approve', [ProductController::class, 'approve']);
    Route::put('products/{id}/reject',  [ProductController::class, 'reject']);
});
