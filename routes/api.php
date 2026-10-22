<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RestaurantController;
use App\Http\Controllers\Api\V1\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Auth
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/auth/2fa/verify', [AuthController::class, 'verifyTwoFactor']);
    Route::post('telegram/webhook', [TelegramWebhookController::class, 'handle']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);

        // 2FA
        Route::post('/auth/2fa/setup', [AuthController::class, 'setupTwoFactor']);
        Route::post('/auth/2fa/confirm', [AuthController::class, 'confirmTwoFactor']);
    });

    Route::prefix('restaurants')->group(function () {
        Route::get('/', [RestaurantController::class, 'index']);
        Route::get('{id}', [RestaurantController::class, 'show']);
        Route::get('{id}/menu', [RestaurantController::class, 'menu']);
        Route::get('{id}/reviews', [RestaurantController::class, 'reviews']);
    });
});
