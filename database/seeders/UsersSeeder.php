<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = DB::table('roles')->pluck('role_id', 'role_name');

        $developerHash = Hash::make('developer12345');
        $managerHash = Hash::make('manager12345');

        $users = [];

        if (isset($roles['developer'])) {
            $users[] = [
                'role_id' => $roles['developer'],
                'user_login' => 'developer',
                'user_password_hash' => $developerHash,
                'password' => $developerHash, // <-- ВАЖНО: стандартное поле Laravel
                'user_full_name' => 'Developer Admin',
                'user_is_active' => 1,
            ];
        }

        if (isset($roles['manager'])) {
            $users[] = [
                'role_id' => $roles['manager'],
                'user_login' => 'manager',
                'user_password_hash' => $managerHash,
                'password' => $managerHash, // <-- ВАЖНО
                'user_full_name' => 'Manager User',
                'user_is_active' => 1,
            ];
        }

        $branchId = null;
        $cityId = null;
        if (Schema::hasTable('branches') && Schema::hasColumn('users', 'branch_id')) {
            $branchId = DB::table('branches')->value('branch_id');
        }
        if (Schema::hasTable('cities') && Schema::hasColumn('users', 'city_id')) {
            $cityId = DB::table('cities')->value('city_id');
        }

        foreach ($users as $user) {
            if ($user['user_login'] === 'manager') {
                if ($branchId) {
                    $user['branch_id'] = $branchId;
                }
                if ($cityId) {
                    $user['city_id'] = $cityId;
                }
            }
            DB::table('users')->updateOrInsert(
                ['user_login' => $user['user_login']],
                $user
            );
        }
    }
}
