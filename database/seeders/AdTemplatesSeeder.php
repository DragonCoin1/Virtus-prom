<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Листовки (template_type = leaflet)
        $leaflets = [
            'Алексей',
            'Евгений',
            'Егор',
            'Роман',
            'Сергей',
            'Олег Михайлович',
            'Андрей',
            'Юрий',
            'Федор',
            'Алексей',
        ];

        foreach ($leaflets as $name) {
            DB::table('ad_templates')->updateOrInsert(
                ['template_name' => $name, 'template_type' => 'leaflet'],
                ['is_active' => 1, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
