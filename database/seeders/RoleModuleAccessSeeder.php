<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleModuleAccessSeeder extends Seeder
{
    public function run(): void
    {
        $roles = DB::table('roles')->pluck('role_id', 'role_name');

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
        ];

        $fullAccessRoles = [
            'developer',
            'general_director',
            'regional_director',
            'branch_director',
        ];

        foreach ($fullAccessRoles as $roleName) {
            if (!isset($roles[$roleName])) {
                continue;
            }
            foreach ($modules as $m) {
                DB::table('role_module_access')->insert([
                    'role_id' => $roles[$roleName],
                    'module_code' => $m,
                    'can_view' => 1,
                    'can_edit' => 1,
                ]);
            }
        }

        if (isset($roles['manager'])) {
            foreach ($modules as $m) {
                DB::table('role_module_access')->insert([
                    'role_id' => $roles['manager'],
                    'module_code' => $m,
                    'can_view' => $m === 'ad_templates' ? 0 : 1,
                    'can_edit' => in_array($m, ['salary', 'ad_templates'], true) ? 0 : 1,
                ]);
            }
        }

        if (isset($roles['promoter'])) {
            foreach (['salary', 'route_actions'] as $m) {
                DB::table('role_module_access')->insert([
                    'role_id' => $roles['promoter'],
                    'module_code' => $m,
                    'can_view' => 1,
                    'can_edit' => $m === 'salary' ? 1 : 0,
                ]);
            }
        }
    }
}
