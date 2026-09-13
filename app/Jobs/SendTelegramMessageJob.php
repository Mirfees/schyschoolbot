<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60]; // Throttling fallback

    public function __construct(
        public string $chatId,
        public string $text,
        public ?string $replyMarkup = null
    ) {}

    public function handle(): void
    {
        try {
            $payload = [
                'chat_id' => $this->chatId,
                'text' => $this->text,
                'parse_mode' => 'HTML',
            ];

            if ($this->replyMarkup) {
                $payload['reply_markup'] = $this->replyMarkup;
            }

            Telegram::sendMessage($payload);

        } catch (TelegramResponseException $e) {
            if ($e->getCode() == 403) {
                // Пользователь заблокировал бота, можно деактивировать аккаунт в БД
                Log::warning("User {$this->chatId} blocked the bot.");
                return;
            }

            if ($e->getCode() == 429) {
                // Too Many Requests, возвращаем в очередь
                $this->release(30);
            }

            throw $e;
        }
    }
}
