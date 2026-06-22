<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Category\Presentation\Controllers\CategoryController;

// GET — public (login shart emas)
// POST, PUT, DELETE — TODO: auth:admin middleware (Admin moduli tayyor bo'lgach qo'shiladi)
Route::prefix('categories')->group(function (): void {
    Route::get('/',              [CategoryController::class, 'index']);
    Route::get('/{category}',   [CategoryController::class, 'show']);
    Route::post('/',             [CategoryController::class, 'store']);
    Route::put('/{category}',   [CategoryController::class, 'update']);
    Route::delete('/{category}', [CategoryController::class, 'destroy']);
});
