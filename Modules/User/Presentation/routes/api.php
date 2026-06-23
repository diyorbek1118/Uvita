<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Presentation\Controllers\UserController;

Route::middleware('auth:api')->prefix('user')->group(function (): void {
    Route::get('profile', [UserController::class, 'profile']);
    Route::put('profile', [UserController::class, 'update']);
});
