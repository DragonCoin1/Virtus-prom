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
        if (!Schema::hasTable('roles') || !Schema::hasTable('users')) {
            return;
        }

        $columns = $this->tableColumns('users');
        $roles = DB::table('roles')->pluck('role_id', 'role_name');

        $developerHash = Hash::make('developer12345');
        $managerHash = Hash::make('manager12345');

        $users = [];

        if (isset($roles['developer'])) {
            $users[] = $this->buildUserRow($columns, [
                'role_id' => $roles['developer'],
                'user_login' => 'developer',
                'user_password_hash' => $developerHash,
                'password' => $developerHash,
                'user_full_name' => 'Developer Admin',
                'user_is_active' => 1,
            ]);
        }

        if (isset($roles['manager'])) {
            $users[] = $this->buildUserRow($columns, [
                'role_id' => $roles['manager'],
                'user_login' => 'manager',
                'user_password_hash' => $managerHash,
                'password' => $managerHash,
                'user_full_name' => 'Manager User',
                'user_is_active' => 1,
            ]);
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
            if (($user['user_login'] ?? null) === 'manager') {
                if ($branchId && in_array('branch_id', $columns, true)) {
                    $user['branch_id'] = $branchId;
                }
                if ($cityId && in_array('city_id', $columns, true)) {
                    $user['city_id'] = $cityId;
                }
            }

            DB::table('users')->updateOrInsert(
                ['user_login' => $user['user_login']],
                $user
            );
        }
    }

    private function buildUserRow(array $columns, array $data): array
    {
        $row = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $columns, true)) {
                $row[$key] = $value;
            }
        }

        if (in_array('created_at', $columns, true) && !isset($row['created_at'])) {
            $row['created_at'] = now();
        }
        if (in_array('updated_at', $columns, true) && !isset($row['updated_at'])) {
            $row['updated_at'] = now();
        }

        return $row;
    }

    private function tableColumns(string $table): array
    {
        try {
            return Schema::getColumnListing($table);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
