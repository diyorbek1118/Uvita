<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Chat\Presentation\Controllers\ChatController;

Route::middleware('auth:api')->prefix('chats')->group(function (): void {
    Route::get('/', [ChatController::class, 'index']);
    Route::post('/', [ChatController::class, 'store']);
    Route::get('{id}', [ChatController::class, 'show'])->whereNumber('id');
    Route::get('{id}/messages', [ChatController::class, 'messages'])->whereNumber('id');
    Route::post('{id}/messages', [ChatController::class, 'sendMessage'])->whereNumber('id');
});
