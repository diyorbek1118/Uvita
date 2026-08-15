<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Deal\Presentation\Controllers\DealController;

Route::middleware('auth:api')->prefix('deals')->group(function (): void {
    Route::get('incoming', [DealController::class, 'incoming']);
    Route::get('outgoing', [DealController::class, 'outgoing']);
    Route::post('/', [DealController::class, 'store']);
    Route::post('{id}/confirm', [DealController::class, 'confirm'])->whereNumber('id');
    Route::post('{id}/complete', [DealController::class, 'complete'])->whereNumber('id');
    Route::post('{id}/cancel', [DealController::class, 'cancel'])->whereNumber('id');
});
