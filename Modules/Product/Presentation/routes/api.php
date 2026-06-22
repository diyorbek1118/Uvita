<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Presentation\Controllers\ProductController;

// Public — login shart emas (faqat active mahsulotlar)
Route::prefix('products')->group(function (): void {
    Route::get('/',           [ProductController::class, 'index']);
    Route::get('/{product}',  [ProductController::class, 'show']);
});

// Write — TODO: auth:manager middleware (Manager moduli tayyor bo'lgach)
Route::prefix('products')->group(function (): void {
    Route::post('/',          [ProductController::class, 'store']);
    Route::put('/{product}',  [ProductController::class, 'update']);
});

// Admin — TODO: auth:admin middleware (Admin moduli tayyor bo'lgach)
Route::prefix('products')->group(function (): void {
    Route::delete('/{product}',         [ProductController::class, 'destroy']);
    Route::patch('/{product}/approve',  [ProductController::class, 'approve']);
    Route::patch('/{product}/reject',   [ProductController::class, 'reject']);
});
