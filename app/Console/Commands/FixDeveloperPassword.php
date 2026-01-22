<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FixDeveloperPassword extends Command
{
    protected $signature = 'user:fix-developer';
    protected $description = 'Создает или исправляет пароль пользователя developer';

    public function handle()
    {
        $roleId = DB::table('roles')->where('role_name', 'developer')->value('role_id');
        
        if (!$roleId) {
            $this->error('Роль developer не найдена. Запустите: php artisan db:seed --class=RolesSeeder');
            return 1;
        }

        $user = User::where('user_login', 'developer')->first();
        
        $password = 'developer12345';
        $passwordHash = Hash::make($password);

        if ($user) {
            $user->user_password_hash = $passwordHash;
            $user->password = $passwordHash;
            $user->user_is_active = 1;
            $user->role_id = $roleId;
            $user->save();
            $this->info('Пароль пользователя developer обновлен');
        } else {
            User::create([
                'user_login' => 'developer',
                'user_full_name' => 'Developer Admin',
                'role_id' => $roleId,
                'user_password_hash' => $passwordHash,
                'password' => $passwordHash,
                'user_is_active' => 1,
            ]);
            $this->info('Пользователь developer создан');
        }

        $this->info('Логин: developer');
        $this->info('Пароль: developer12345');
        
        return 0;
    }
}
