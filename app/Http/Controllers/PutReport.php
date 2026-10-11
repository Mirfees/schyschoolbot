<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class PutReport extends Controller
{
    public static function handle(User $user, string $type, ?string $category = null, ?string $text = null, bool $isUrgent = false): void
    {
        try {
            \App\Models\Report::create([
                'created_at' => now(),
                'class_name' => $user->class_name,
                'telegram_id' => $user->telegram_id,
                'type' => $type,
                'category' => $category,
                'text' => $text,
                'urgency' => $isUrgent ? 'Внеплановое (срочное)' : 'Плановое',
            ]);

        } catch (\Throwable $e) {
            Log::error('Ошибка записи SQLite: ' . $e->getMessage());
        }
    }
}
