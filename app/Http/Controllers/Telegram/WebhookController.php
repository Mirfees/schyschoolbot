<?php

declare(strict_types=1);

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\Telegram\BotLogicService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request, string $token, BotLogicService $botService): Response
    {
        // Простая защита: проверяем токен в URL
        if ($token !== config('telegram.bots.mybot.token')) {
            abort(403);
        }

        try {
            $update = Telegram::commandsHandler(true);
            $botService->handleUpdate($update);
        } catch (\Throwable $e) {
            Log::error('Telegram Webhook Error: ' . $e->getMessage());
        }

        return response('OK', 200);
    }
}
