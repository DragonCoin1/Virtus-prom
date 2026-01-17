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
            'dashboard',
            'promoters',
            'routes',
            'route_actions',
            'cards',
            'interviews',
            'salary',
            'keys_registry',
            'reports',
        ];

        // owner: всё view+edit
        foreach ($modules as $m) {
            DB::table('role_module_access')->insert([
                'role_id' => $roles['owner'],
                'module_code' => $m,
                'can_view' => 1,
                'can_edit' => 1,
            ]);
        }

        // manager: почти всё (без каких-то админских можно потом урезать)
        foreach ($modules as $m) {
            DB::table('role_module_access')->insert([
                'role_id' => $roles['manager'],
                'module_code' => $m,
                'can_view' => 1,
                'can_edit' => ($m === 'reports') ? 0 : 1,
            ]);
        }

        // promoter: только dashboard + salary + route_actions (условно)
        foreach (['dashboard', 'salary', 'route_actions'] as $m) {
            DB::table('role_module_access')->insert([
                'role_id' => $roles['promoter'],
                'module_code' => $m,
                'can_view' => 1,
                'can_edit' => 0,
            ]);
        }
    }
}
