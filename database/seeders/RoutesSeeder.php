<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoutesSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('routes')) return;

        $hasArea = Schema::hasColumn('routes', 'route_area');
        $hasType = Schema::hasColumn('routes', 'route_type');
        $hasActive = Schema::hasColumn('routes', 'is_active');

        $types = ['city', 'private', 'mixed'];
        $areas = ['Центр', 'Север', 'Юг', 'Запад', 'Восток', 'Пригород'];

        $rows = [];
        for ($i = 1; $i <= 60; $i++) {
            $row = [
                'route_code' => 'R' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
            ];

            if ($hasArea) $row['route_area'] = $areas[array_rand($areas)];
            if ($hasType) $row['route_type'] = $types[array_rand($types)];
            if ($hasActive) $row['is_active'] = 1;

            $rows[] = $row;
        }

        DB::table('routes')->insert($rows);
    }
}
