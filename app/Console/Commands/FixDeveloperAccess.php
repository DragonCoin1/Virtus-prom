<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDeveloperAccess extends Command
{
    protected $signature = 'dev:fix-access';
    protected $description = 'Исправляет права доступа для developer (устанавливает все права)';

    public function handle()
    {
        $developerRole = DB::table('roles')->where('role_name', 'developer')->first();
        
        if (!$developerRole) {
            $this->error('Роль developer не найдена! Запустите: php artisan db:seed --class=RolesSeeder');
            return 1;
        }
        
        $modules = [
            'promoters',
            'routes',
            'route_actions',
            'cards',
            'interviews',
            'salary',
            'keys_registry',
            'reports',
            'ad_templates',
            'ad_residuals',
            'instructions',
        ];
        
        $fixed = 0;
        foreach ($modules as $module) {
            $exists = DB::table('role_module_access')
                ->where('role_id', $developerRole->role_id)
                ->where('module_code', $module)
                ->exists();
            
            if ($exists) {
                DB::table('role_module_access')
                    ->where('role_id', $developerRole->role_id)
                    ->where('module_code', $module)
                    ->update([
                        'can_view' => 1,
                        'can_edit' => 1,
                    ]);
            } else {
                DB::table('role_module_access')->insert([
                    'role_id' => $developerRole->role_id,
                    'module_code' => $module,
                    'can_view' => 1,
                    'can_edit' => 1,
                ]);
            }
            $fixed++;
        }
        
        $this->info("Исправлено прав доступа для developer: {$fixed} модулей");
        $this->info("Все модули теперь имеют can_view=1 и can_edit=1");
        
        return 0;
    }
}
