<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Category\Presentation\Controllers\CategoryController;

// Public
Route::prefix('categories')->group(function (): void {
    Route::get('/',            [CategoryController::class, 'index']);
    Route::get('/{category}',  [CategoryController::class, 'show']);
});

// Admin only
Route::middleware(['auth:sanctum', 'role.admin'])->prefix('categories')->group(function (): void {
    Route::post('/',             [CategoryController::class, 'store']);
    Route::put('/{category}',    [CategoryController::class, 'update']);
    Route::delete('/{category}', [CategoryController::class, 'destroy']);
});
