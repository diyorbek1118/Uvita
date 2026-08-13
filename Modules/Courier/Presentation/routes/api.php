<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Courier\Presentation\Controllers\AdminCourierOperationsController;
use Modules\Courier\Presentation\Controllers\CourierController;

Route::middleware(['auth:sanctum', 'role.courier', 'throttle:courier-actions'])->prefix('courier')->group(function (): void {
    Route::get('profile', [CourierController::class, 'profile']);
    Route::put('profile', [CourierController::class, 'updateProfile']);
    Route::put('availability', [CourierController::class, 'availability']);
    Route::get('history', [CourierController::class, 'history']);
    Route::get('stats', [CourierController::class, 'stats']);
    Route::get('earnings', [CourierController::class, 'earnings']);
    Route::post('devices', [CourierController::class, 'registerDevice']);
    Route::delete('devices/{id}', [CourierController::class, 'removeDevice']);
    Route::get('notifications', [CourierController::class, 'notifications']);
    Route::put('notifications/{id}/read', [CourierController::class, 'readNotification']);
    Route::post('locations', [CourierController::class, 'saveLocation'])
        ->middleware('throttle:courier-location');
    Route::get('support', [CourierController::class, 'support']);
    Route::post('support', [CourierController::class, 'createSupport']);
});

Route::middleware(['auth:sanctum', 'role.admin'])->prefix('admin')->group(function (): void {
    Route::get('courier-support', [AdminCourierOperationsController::class, 'support']);
    Route::put('courier-support/{id}', [AdminCourierOperationsController::class, 'resolveSupport']);
    Route::get('couriers/{id}/location', [AdminCourierOperationsController::class, 'latestLocation']);
});

Route::middleware(['auth:sanctum', 'role.super_admin'])->prefix('super')->group(function (): void {
    Route::get('courier-payouts', [AdminCourierOperationsController::class, 'payouts']);
    Route::post('courier-payouts', [AdminCourierOperationsController::class, 'createPayout']);
    Route::put('courier-payouts/{id}/paid', [AdminCourierOperationsController::class, 'markPayoutPaid']);
});
