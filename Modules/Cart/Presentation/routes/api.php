<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Cart\Presentation\Controllers\CartController;

Route::middleware('auth:api')->group(function (): void {
    Route::get('cart',          [CartController::class, 'index']);
    Route::post('cart/items',   [CartController::class, 'add']);
    Route::delete('cart/items', [CartController::class, 'remove']);
    Route::delete('cart',       [CartController::class, 'clear']);
});
