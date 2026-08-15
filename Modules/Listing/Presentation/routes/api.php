<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Listing\Presentation\Controllers\ListingController;

// Public
Route::prefix('listings')->group(function (): void {
    Route::get('/', [ListingController::class, 'index']);
    Route::get('/{id}', [ListingController::class, 'show'])->whereNumber('id');
});

// Auth — sotuvchi e'lonlari
Route::middleware('auth:api')->prefix('listings')->group(function (): void {
    Route::post('/', [ListingController::class, 'store']);
    Route::post('upload-video', [ListingController::class, 'uploadVideo']);
    Route::put('/{id}', [ListingController::class, 'update'])->whereNumber('id');
    Route::delete('/{id}', [ListingController::class, 'destroy'])->whereNumber('id');
});

Route::middleware('auth:api')->prefix('my')->group(function (): void {
    Route::get('listings', [ListingController::class, 'myListings']);
});
