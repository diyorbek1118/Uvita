<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Courier\Presentation\Controllers\CourierController;

Route::middleware(['auth:sanctum', 'role.courier'])->prefix('courier')->group(function (): void {
    Route::get('profile', [CourierController::class, 'profile']);
    Route::get('history', [CourierController::class, 'history']);
    Route::get('stats',   [CourierController::class, 'stats']);
});
