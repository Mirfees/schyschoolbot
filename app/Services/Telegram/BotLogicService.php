<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Jobs\NotifyAdminJob;
use App\Jobs\SendToGoogleSheetsJob;
use App\Models\AccessCode;
use App\Models\User;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Keyboard\Keyboard;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class BotLogicService
{
    public function __construct(private UserStateService $stateService) {}

    public function handleUpdate(Update $update): void
    {
        $message = $update->getMessage();
        $callback = $update->getCallbackQuery();

        $chatId = $message ? $message->chat->id : $callback->message->chat->id;
        $text = $message ? $message->text : null;

        $user = User::where('telegram_id', $chatId)->first();

        // 1. Если пользователя нет (первый вход)
        if (!$user) {
            $this->handleFirstLogin($chatId, $text);
            return;
        }

        // 2. Обработка команд в любом состоянии
        if ($text === '/start' || $text === 'Помощь / правила') {
            $this->showMainMenu($chatId);
            return;
        }

        // 3. FSM (Конечный автомат)
        $state = $this->stateService->getState($chatId);

        match ($state) {
            UserStateService::STATE_IDLE => $this->handleIdleMenu($chatId, $text, $user),
            UserStateService::STATE_WAITING_CATEGORY => $this->handleCategorySelection($chatId, $text),
            UserStateService::STATE_WAITING_TEXT => $this->handleTextObservation($chatId, $text, $user),
            default => $this->showMainMenu($chatId),
        };
    }

    private function handleFirstLogin(int $chatId, ?string $text): void
    {
        $state = $this->stateService->getState($chatId);

        if ($state !== UserStateService::STATE_WAITING_CODE) {
            $warning = "⚠️ <b>ПРЕДУПРЕЖДЕНИЕ:</b> Если прямо сейчас кому-то угрожает опасность, не пиши сюда, а сразу зови взрослого рядом или звони на телефон доверия (87472570462). Бот не для экстренной помощи.\n\nПожалуйста, введите код доступа для входа:";

            Telegram::sendMessage(['chat_id' => $chatId, 'text' => $warning, 'parse_mode' => 'HTML']);
            $this->stateService->setState($chatId, UserStateService::STATE_WAITING_CODE);
            return;
        }

        // Проверяем код
        $accessCode = AccessCode::where('code', $text)->where('is_active', true)->first();

        if (!$accessCode) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => '❌ Неверный или отозванный код. Попробуйте еще раз:']);
            return;
        }

        // Регистрируем наблюдателя и привязываем к классу
        try {
            $user = User::updateOrCreate(
                ['telegram_id' => $chatId],
                [
                    'role'       => 'student',
                    'class_name' => $accessCode->class_name,
                    'name'       => 'Ученик ' . $accessCode->class_name,
                    'email'      => $chatId . '@schoolbot.local',
                    'password'   => Hash::make(Str::random(24)),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Ошибка регистрации ученика-наблюдателя', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage()
            ]);

            // Бросаем исключение дальше или уведомляем пользователя
            throw $e;
        }


        $this->stateService->clearState($chatId);
        $this->showMainMenu($chatId, "✅ Код принят. Вы успешно авторизованы как наблюдатель класса {$accessCode->class_name}.");
    }

    private function showMainMenu(int $chatId, string $prefix = ''): void
    {
        $keyboard = Keyboard::make()
            ->setResizeKeyboard(true)
            ->row([Keyboard::button('Сообщить о наблюдении')])
            ->row([Keyboard::button('Наблюдений нет')])
            ->row([Keyboard::button('Помощь / правила')]);

        $text = $prefix . "\n\nВыберите действие:\n⚠️ Помните: при экстренной угрозе звоните 87472570462.";

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => trim($text),
            'reply_markup' => $keyboard
        ]);

        $this->stateService->setState($chatId, UserStateService::STATE_IDLE);
    }

    public function handleNoObservations(User $user, int $chatId): void
    {
        $weekKey = 'week_' . now()->weekOfYear . '_year_' . now()->year;

        // Ключи для кэша
        $hasReportedKey = "reported_{$user->telegram_id}_{$weekKey}"; // Общая отметка за неделю
        $noObsKey = "no_obs_{$user->telegram_id}_{$weekKey}";         // Защита от дубля "Наблюдений нет"

        if (Cache::has($noObsKey)) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => '✅ Вы уже отмечались на этой неделе. Спасибо!'
            ]);
            return;
        }

        // Запоминаем, что ученик отметился (до конца текущей недели)
        Cache::put($hasReportedKey, true, now()->endOfWeek());
        // Блокируем повторное нажатие "Наблюдений нет"
        Cache::put($noObsKey, true, now()->endOfWeek());

        SendToGoogleSheetsJob::dispatch($user, 'Наблюдений нет', null, null, false);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => '✅ Отметка принята. Спасибо, что на связи!'
        ]);
    }

    private function handleIdleMenu(int $chatId, ?string $text, User $user): void
    {
        if ($text === 'Наблюдений нет') {
            // Отправляем в Google Sheets с пометкой "Наблюдений нет"
            BotLogicService::handleNoObservations($user, $chatId);
            return;
        }

        if ($text === 'Сообщить о наблюдении') {
            $keyboard = Keyboard::make()
                ->setResizeKeyboard(true)
                ->row([Keyboard::button('Травля / буллинг')])
                ->row([Keyboard::button('Резкое изменение состояния сверстника')])
                ->row([Keyboard::button('Тревожные или пугающие высказывания')])
                ->row([Keyboard::button('Другое')]);

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Выберите категорию:',
                'reply_markup' => $keyboard
            ]);
            $this->stateService->setState($chatId, UserStateService::STATE_WAITING_CATEGORY);
        }
    }

    private function handleCategorySelection(int $chatId, ?string $text): void
    {
        $validCategories = ['Травля / буллинг', 'Резкое изменение состояния сверстника', 'Тревожные или пугающие высказывания', 'Другое'];

        if (!in_array($text, $validCategories)) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => 'Пожалуйста, используйте кнопки ниже для выбора категории.']);
            return;
        }

        $this->stateService->setState($chatId, UserStateService::STATE_WAITING_TEXT, ['category' => $text]);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => 'Опиши коротко, что заметил:',
            'reply_markup' => Keyboard::remove() // Убираем кнопки
        ]);
    }

    private function handleTextObservation(int $chatId, ?string $text, User $user): void
    {
        $weekKey = 'week_' . now()->weekOfYear . '_year_' . now()->year;
        Cache::put("reported_{$user->telegram_id}_{$weekKey}", true, now()->endOfWeek());

        $payload = $this->stateService->getPayload($chatId);
        $category = $payload['category'] ?? 'Другое';

        // 1. Отправка в Google Таблицу (Фоновая задача)
        SendToGoogleSheetsJob::dispatch($user, 'Сигнал', $category, $text, true);

        // 2. Срочное уведомление психологу (Фоновая задача)
        NotifyAdminJob::dispatch($user, $category, $text);

        $this->stateService->clearState($chatId);
        $this->showMainMenu($chatId, "✅ Спасибо, сигнал принят и передан куратору.");
    }
}
