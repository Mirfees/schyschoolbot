<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Telegram\WebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Защита Webhook через токен в URL (базовая защита)
Route::post('/webhook/telegram/{token}', [WebhookController::class, 'handle'])
    ->where('token', '.*')
    ->name('telegram.webhook');// Валидация токена происходит внутри контроллера
