<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = DB::table('roles')->pluck('role_id', 'role_name');

        $ownerHash = Hash::make('owner12345');
        $managerHash = Hash::make('manager12345');

        $users = [
            [
                'role_id' => $roles['owner'],
                'user_login' => 'owner',
                'user_password_hash' => $ownerHash,
                'password' => $ownerHash, // <-- ВАЖНО: стандартное поле Laravel
                'user_full_name' => 'Owner Admin',
                'user_is_active' => 1,
            ],
            [
                'role_id' => $roles['manager'],
                'user_login' => 'manager',
                'user_password_hash' => $managerHash,
                'password' => $managerHash, // <-- ВАЖНО
                'user_full_name' => 'Manager User',
                'user_is_active' => 1,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['user_login' => $user['user_login']],
                $user
            );
        }
    }
}
