<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Presentation\Controllers\UserController;

// Public — foydalanuvchi profili (sotuvchi/xaridor)
Route::prefix('users')->group(function (): void {
    Route::get('{id}', [UserController::class, 'show'])->whereNumber('id');
});

Route::middleware('auth:api')->prefix('user')->group(function (): void {
    Route::get('profile', [UserController::class, 'profile']);
    Route::put('profile', [UserController::class, 'update']);
});
