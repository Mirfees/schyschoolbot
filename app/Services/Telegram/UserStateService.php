<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Cache;

class UserStateService
{
    private const TTL = 3600; // Храним состояние 1 час

    public const STATE_WAITING_CODE = 'waiting_code';
    public const STATE_IDLE = 'idle'; // Главное меню
    public const STATE_WAITING_CATEGORY = 'waiting_category';
    public const STATE_WAITING_TEXT = 'waiting_text';

    public function setState(int|string $telegramId, string $state, array $payload = []): void
    {
        Cache::put("tg_state:{$telegramId}", [
            'state' => $state,
            'payload' => $payload
        ], self::TTL);
    }

    public function getState(int|string $telegramId): string
    {
        $data = Cache::get("tg_state:{$telegramId}");
        return $data['state'] ?? self::STATE_IDLE;
    }

    public function getPayload(int|string $telegramId): array
    {
        $data = Cache::get("tg_state:{$telegramId}");
        return $data['payload'] ?? [];
    }

    public function clearState(int|string $telegramId): void
    {
        Cache::forget("tg_state:{$telegramId}");
    }
}
