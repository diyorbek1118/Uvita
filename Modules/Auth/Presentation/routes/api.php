<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Presentation\Controllers\AuthController;

Route::prefix('auth')->group(function (): void {
    Route::post('otp/send',   [AuthController::class, 'sendOtp']);
    Route::post('otp/verify', [AuthController::class, 'verifyOtp']);

    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:api');
});
