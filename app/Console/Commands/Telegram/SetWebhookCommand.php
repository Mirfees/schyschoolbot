<?php

declare(strict_types=1);

namespace App\Console\Commands\Telegram;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Exceptions\TelegramSDKException;

class SetWebhookCommand extends Command
{
    protected $signature = 'bot:set-webhook';
    protected $description = 'Установить Webhook URL для Telegram бота из конфигурации';

    public function handle(): int
    {
        $url = env('TELEGRAM_WEBHOOK_URL');

        if (!$url) {
            $this->error('TELEGRAM_WEBHOOK_URL не задан в .env файле.');
            return Command::FAILURE;
        }

        try {
            $response = Telegram::setWebhook(['url' => $url]);

            if ($response) {
                $this->info("Webhook успешно установлен: {$url}");
                return Command::SUCCESS;
            }

            $this->error('Не удалось установить Webhook. Telegram API вернул false.');
            return Command::FAILURE;

        } catch (TelegramSDKException $e) {
            $this->error('Ошибка SDK: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
