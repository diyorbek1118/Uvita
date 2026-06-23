<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Presentation\Controllers\ClickWebhookController;
use Modules\Payment\Presentation\Controllers\PaymentController;
use Modules\Payment\Presentation\Controllers\PaymeWebhookController;
use Modules\Payment\Presentation\Controllers\UzumWebhookController;

// Customer — to'lov URL olish (login talab qilinadi)
Route::middleware('auth:api')->group(function () {
    Route::post('payment/create', [PaymentController::class, 'create']);
});

// Webhook — provayderlar tomonidan chaqiriladi (autentifikatsiya webhook handler ichida)
Route::prefix('payment')->group(function (): void {
    Route::post('payme/webhook', PaymeWebhookController::class);
    Route::post('click/webhook', ClickWebhookController::class);
    Route::post('uzum/webhook',  UzumWebhookController::class);
});
