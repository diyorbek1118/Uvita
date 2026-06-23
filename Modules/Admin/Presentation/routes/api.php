<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Presentation\Controllers\AdminCourierController;
use Modules\Admin\Presentation\Controllers\AdminOrderController;
use Modules\Admin\Presentation\Controllers\AdminProductController;
use Modules\Admin\Presentation\Controllers\AdminReviewController;
use Modules\Admin\Presentation\Controllers\AdminSettingsController;
use Modules\Admin\Presentation\Controllers\AdminStaffController;
use Modules\Admin\Presentation\Controllers\AdminTransactionController;
use Modules\Admin\Presentation\Controllers\AdminUserController;
use Modules\Admin\Presentation\Controllers\StaffAuthController;

Route::post('staff/login', [StaffAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('staff/logout', [StaffAuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'role.admin'])->prefix('admin')->group(function (): void {
    // Products
    Route::get('products/pending',      [AdminProductController::class, 'pendingProducts']);
    Route::get('products',              [AdminProductController::class, 'allProducts']);
    Route::put('products/{id}/approve', [AdminProductController::class, 'approve']);
    Route::put('products/{id}/reject',  [AdminProductController::class, 'reject']);

    // Orders
    Route::get('orders/delivery-issues', [AdminOrderController::class, 'deliveryIssues']);
    Route::get('orders/stats',           [AdminOrderController::class, 'stats']);

    // Couriers
    Route::get('couriers/available',          [AdminCourierController::class, 'available']);
    Route::get('couriers',                    [AdminCourierController::class, 'index']);
    Route::get('couriers/{id}',               [AdminCourierController::class, 'show']);
    Route::put('couriers/{id}/toggle-active', [AdminCourierController::class, 'toggleActive']);

    // Reviews
    Route::get('reviews/stats', [AdminReviewController::class, 'stats']);
});

Route::middleware(['auth:sanctum', 'role.super_admin'])->prefix('super')->group(function (): void {
    // Staff (CRUD)
    Route::get('staff',                    [AdminStaffController::class, 'index']);
    Route::get('staff/{id}',               [AdminStaffController::class, 'show']);
    Route::post('staff',                   [AdminStaffController::class, 'store']);
    Route::put('staff/{id}',               [AdminStaffController::class, 'update']);
    Route::delete('staff/{id}',            [AdminStaffController::class, 'destroy']);
    Route::put('staff/{id}/toggle-active', [AdminStaffController::class, 'toggleActive']);

    // Users (read-only)
    Route::get('users',      [AdminUserController::class, 'index']);
    Route::get('users/{id}', [AdminUserController::class, 'show']);

    // Transactions
    Route::get('transactions/stats', [AdminTransactionController::class, 'stats']);
    Route::get('transactions',       [AdminTransactionController::class, 'index']);

    // Settings
    Route::get('settings',       [AdminSettingsController::class, 'index']);
    Route::put('settings',       [AdminSettingsController::class, 'update']);
    Route::patch('settings/bulk', [AdminSettingsController::class, 'bulkUpdate']);
});
