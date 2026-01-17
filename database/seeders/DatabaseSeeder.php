<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ВАЖНО: пользователей/роли здесь не сидим,
        // чтобы не снести реальный логин owner и права.
        // Сидим только тестовые рабочие данные проекта.
        $this->call([
            FullTestSeeder::class,
        ]);
    }
}
