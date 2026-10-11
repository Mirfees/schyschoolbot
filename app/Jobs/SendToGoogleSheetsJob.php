<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Revolution\Google\Sheets\Facades\Sheets;
use Illuminate\Support\Facades\Log;

class SendToGoogleSheetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public User $user,
        public string $type,
        public ?string $category = null,
        public ?string $text = null,
        public bool $isUrgent = false
    ) {}

    public function handle(): void
    {
        try {
            // Структура строки согласно ТЗ
            $row = [
                now()->format('d.m.Y H:i:s'),
                $this->user->class_name,
                (string) $this->user->telegram_id, // Идентификатор, известный психологу
                $this->type,
                $this->category ?? '-',
                $this->text ?? '-',
                $this->isUrgent ? 'Внеплановое (срочное)' : 'Плановое'
            ];

            Sheets::spreadsheet(config('google.post_spreadsheet_id'))
                ->sheet(config('google.post_sheet_id'))
                ->append([$row]);

        } catch (\Throwable $e) {
            Log::error('Ошибка отправки в Google Sheets: ' . $e->getMessage());
            $this->release(60); // Повтор через минуту при сбое API
        }
    }
}
