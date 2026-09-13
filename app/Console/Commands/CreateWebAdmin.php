<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateWebAdmin extends Command
{
    protected $signature = 'admin:create {email : Email администратора} {--password= : Указать пароль вручную}';

    protected $description = 'Создает администратора для доступа к Web-панели управления кодами';

    public function handle(): int
    {
        $email = $this->argument('email');
        // Если пароль не передан, генерируем случайный
        $password = $this->option('password') ?? Str::random(12);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Психолог-куратор',
                'password' => Hash::make($password),
                'role' => 'admin',
            ]
        );

        if (!$user->wasRecentlyCreated) {
            $user->update(['password' => Hash::make($password)]);
            $this->info("Пароль для существующего администратора {$email} обновлен.");
        } else {
            $this->info("Администратор успешно создан!");
        }

        $this->warn("Email: {$email}");
        $this->warn("Password: {$password}");

        return Command::SUCCESS;
    }
}
