<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $update = Telegram::getWebhookUpdate();

        $message = $update->getMessage();

        if ($message && $message->has('text')) {
            $chatId = $message->getChat()->getId();
            $text = $message->getText();

            // Echo: отправляем назад тот же текст
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        }

        // Telegram ждет ответ 200 OK, чтобы понять, что webhook доставлен
        return response()->json(['status' => 'success']);
    }
}
