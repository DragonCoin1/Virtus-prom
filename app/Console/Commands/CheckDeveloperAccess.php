<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDeveloperAccess extends Command
{
    protected $signature = 'dev:check-access';
    protected $description = 'Проверяет права доступа для developer';

    public function handle()
    {
        $developerRole = DB::table('roles')->where('role_name', 'developer')->first();
        
        if (!$developerRole) {
            $this->error('Роль developer не найдена!');
            return 1;
        }
        
        $this->info("Роль developer найдена (ID: {$developerRole->role_id})");
        
        $access = DB::table('role_module_access')
            ->where('role_id', $developerRole->role_id)
            ->get();
        
        if ($access->isEmpty()) {
            $this->error('Нет записей в role_module_access для developer!');
            $this->info('Запустите: php artisan db:seed --class=RoleModuleAccessSeeder');
            return 1;
        }
        
        $this->info("\nПрава доступа developer:");
        $this->table(
            ['Модуль', 'Просмотр', 'Редактирование'],
            $access->map(function ($row) {
                return [
                    $row->module_code,
                    $row->can_view ? '✓' : '✗',
                    $row->can_edit ? '✓' : '✗',
                ];
            })->toArray()
        );
        
        $missingEdit = $access->where('can_edit', 0)->pluck('module_code');
        if ($missingEdit->isNotEmpty()) {
            $this->warn("\nМодули без права редактирования: " . $missingEdit->implode(', '));
        }
        
        return 0;
    }
}
