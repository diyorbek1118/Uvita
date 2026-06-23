<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Presentation\Controllers\StaffAuthController;

Route::post('staff/login',  [StaffAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('staff/logout', [StaffAuthController::class, 'logout']);
});
