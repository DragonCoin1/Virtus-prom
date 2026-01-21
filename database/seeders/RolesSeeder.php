<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'developer',
            'general_director',
            'regional_director',
            'branch_director',
            'manager',
            'promoter',
        ] as $roleName) {
            DB::table('roles')->updateOrInsert(
                ['role_name' => $roleName],
                ['role_name' => $roleName]
            );
        }
    }
}
