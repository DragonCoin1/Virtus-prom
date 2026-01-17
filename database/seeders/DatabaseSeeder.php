<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (Schema::hasTable('roles')) {
            $this->call(RolesSeeder::class);
        }

        if (Schema::hasTable('users')) {
            $this->call(UsersSeeder::class);
        }

        // Сидим только тестовые рабочие данные проекта.
        $this->call([
            FullTestSeeder::class,
        ]);
    }
}
