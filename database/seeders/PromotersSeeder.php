<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PromotersSeeder extends Seeder
{
    public function run(): void
    {
        // Чтобы сидер можно было запускать много раз без ошибок
        DB::table('promoters')->truncate();

        DB::table('promoters')->insert([
            [
                'promoter_full_name' => 'Иванов Иван Иванович',
                'promoter_phone' => '9001112233',
                'promoter_status' => 'active',
                'hired_at' => '2025-12-01',
                'fired_at' => null,
                'promoter_comment' => 'Тестовый промоутер',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'promoter_full_name' => 'Петров Пётр Петрович',
                'promoter_phone' => '9002223344',
                'promoter_status' => 'trainee',
                'hired_at' => '2026-01-05',
                'fired_at' => null,
                'promoter_comment' => 'Стажёр',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'promoter_full_name' => 'Сидорова Анна Сергеевна',
                'promoter_phone' => '9003334455',
                'promoter_status' => 'paused',
                'hired_at' => '2025-11-10',
                'fired_at' => null,
                'promoter_comment' => 'Пауза по личным причинам',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'promoter_full_name' => 'Кузнецов Максим Олегович',
                'promoter_phone' => '9004445566',
                'promoter_status' => 'fired',
                'hired_at' => '2025-10-01',
                'fired_at' => '2025-12-20',
                'promoter_comment' => 'Уволен',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
