<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Rating\Presentation\Controllers\RatingController;

Route::prefix('ratings')->group(function (): void {
    Route::get('user/{userId}', [RatingController::class, 'forUser'])->whereNumber('userId');
    // Mahsulot (e'lon) bo'yicha sharhlar — hammaga ochiq
    Route::get('listing/{listingId}', [RatingController::class, 'forListing'])->whereNumber('listingId');
});

Route::middleware('auth:api')->prefix('ratings')->group(function (): void {
    Route::post('/', [RatingController::class, 'store']);
    // Xaridor sotib olgan mahsulotga sharh yozadi
    Route::post('listing/{listingId}', [RatingController::class, 'storeListing'])->whereNumber('listingId');
    Route::get('listing/{listingId}/status', [RatingController::class, 'reviewStatus'])->whereNumber('listingId');
});
