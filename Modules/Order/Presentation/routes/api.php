<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Order\Presentation\Controllers\OrderController;

// ─── Customer (auth:api) ──────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function (): void {
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::delete('orders/{id}', [OrderController::class, 'cancel']);
    Route::get('orders/{id}/delivery-code', [OrderController::class, 'deliveryCode']);
    Route::post('orders/{id}/pay/retry', [OrderController::class, 'payRetry']);
});

// ─── Manager ─────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role.manager'])->prefix('manager')->group(function (): void {
    Route::get('orders', [OrderController::class, 'paidOrders']);
    Route::get('orders/{id}', [OrderController::class, 'managerShow']);
    Route::put('orders/{id}/items', [OrderController::class, 'updateItems']);
    Route::put('orders/{id}/confirm', [OrderController::class, 'confirm']);
    Route::put('orders/{id}/ready', [OrderController::class, 'readyToDeliver']);
    Route::delete('orders/{id}', [OrderController::class, 'managerCancel']);
});

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role.admin'])->prefix('admin')->group(function (): void {
    Route::get('orders', [OrderController::class, 'adminOrders']);
    Route::get('orders/{id}', [OrderController::class, 'adminShow']);
    Route::put('orders/{id}/assign-courier', [OrderController::class, 'assignCourier']);
    Route::put('orders/{id}/resolve-issue', [OrderController::class, 'resolveIssue']);
});

// ─── Courier ─────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role.courier', 'throttle:courier-actions'])->prefix('courier')->group(function (): void {
    Route::get('routes', [OrderController::class, 'courierRoutes']);
    Route::put('routes/accept', [OrderController::class, 'acceptCourierBatch']);
    Route::get('orders', [OrderController::class, 'courierOrders']);
    Route::get('orders/{id}', [OrderController::class, 'courierShow']);
    Route::put('orders/{id}/accept', [OrderController::class, 'accept']);
    Route::put('orders/{id}/reject', [OrderController::class, 'rejectAssignment']);
    Route::put('orders/{id}/delivered', [OrderController::class, 'markDelivered']);
    Route::put('orders/{id}/not-found', [OrderController::class, 'notFound']);
});
