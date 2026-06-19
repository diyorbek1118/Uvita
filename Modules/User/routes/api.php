<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\UserController;

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/profile',  [UserController::class, 'profile']);
    Route::put('/profile',  [UserController::class, 'update']);
});