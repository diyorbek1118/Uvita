<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Presentation\Controllers\AuthController;

Route::prefix('auth')->group(function (): void {
    Route::post('check',          [AuthController::class, 'check']);
    Route::post('otp/send',       [AuthController::class, 'sendOtp']);
    Route::post('otp/confirm',    [AuthController::class, 'confirmOtp']);
    Route::post('otp/verify',     [AuthController::class, 'verifyOtp']);
    Route::post('register',      [AuthController::class, 'register']);
    Route::post('login',         [AuthController::class, 'login']);
    Route::post('password/reset', [AuthController::class, 'resetPassword']);

    Route::post('password/change', [AuthController::class, 'changePassword'])
        ->middleware('auth:api');

    Route::post('phone/change', [AuthController::class, 'changePhone'])
        ->middleware('auth:api');

    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:api');
});
