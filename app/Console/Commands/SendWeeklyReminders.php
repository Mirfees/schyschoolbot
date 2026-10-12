<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendWeeklyReminders extends Command
{
    protected $signature = 'bot:weekly-reminders';
    protected $description = 'Рассылка еженедельных напоминаний наблюдателям';

    public function handle(): void
    {
        $weekKey = 'week_' . now()->weekOfYear . '_year_' . now()->year;
        $users = User::where('role', 'student')->get();

        foreach ($users as $user) {
            $hasReportedKey = "reported_{$user->telegram_id}_{$weekKey}";

            // Если ученик уже отметился на этой неделе, пропускаем[cite: 1]
            if (Cache::has($hasReportedKey)) {
                continue;
            }

            if (!$user->telegram_id) {
                continue; // Пропускаем пользователей без telegram_id
            }

            if (!$user->access_code) {
                continue; // Пропускаем заблокированных пользователей
            }

            try {
                Telegram::sendMessage([
                    'chat_id' => $user->telegram_id,
                    'text' => 'Пора отметиться за эту неделю. Всё спокойно или есть что сообщить?'
                ]);

                // Избегаем лимитов Telegram (Too Many Requests)
                usleep(50000); // 0.05 сек
            } catch (\Throwable $e) {
                // Игнорируем заблокировавших бота
                continue;
            }
        }
    }
}
