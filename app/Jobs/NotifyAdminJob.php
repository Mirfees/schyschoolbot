<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

class NotifyAdminJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public User $user,
        public string $category,
        public string $text
    ) {}

    public function handle(): void
    {
        $adminChatId = config('telegram.curator_chat_id');

        if (!$adminChatId) {
            Log::error('TELEGRAM_CURATOR_CHAT_ID не задан в .env');
            return;
        }

        // Формирование уведомления с классом, категорией и текстом
        $message = sprintf(
            "🚨 <b>СРОЧНЫЙ СИГНАЛ!</b>\n\n<b>Класс:</b> %s\n<b>Категория:</b> %s\n<b>Сообщение:</b> %s\n<b>Время:</b> %s",
            $this->user->class_name,
            $this->category,
            $this->text,
            now()->format('d.m.Y H:i:s')
        );

        Telegram::sendMessage([
            'chat_id' => $adminChatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);
    }
}
