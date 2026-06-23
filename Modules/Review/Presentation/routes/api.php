<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Review\Presentation\Controllers\ReviewController;

// Public — mahsulot sharhlari
Route::get('products/{productId}/reviews', [ReviewController::class, 'productReviews']);

// Customer (auth:api)
Route::middleware('auth:api')->group(function () {
    Route::get('reviews/my',    [ReviewController::class, 'myReviews']);
    Route::post('reviews',      [ReviewController::class, 'store']);
    Route::put('reviews/{id}',  [ReviewController::class, 'update']);
});

// Admin (auth:sanctum + role.admin)
Route::middleware(['auth:sanctum', 'role.admin'])->prefix('admin')->group(function () {
    Route::get('reviews/pending',          [ReviewController::class, 'pendingReviews']);
    Route::put('reviews/{id}/approve',     [ReviewController::class, 'approve']);
    Route::put('reviews/{id}/reject',      [ReviewController::class, 'reject']);
});
