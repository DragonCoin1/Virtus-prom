<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class FullTestSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'ad_residuals',
            'instructions',
            'promoter_salaries',
            'role_module_access',
            'users',
            'roles',
            'branches',
            'cities',
            'route_action_templates',
            'route_actions',
            'ad_templates',
            'interviews',
            'salary_adjustments',
            'routes',
            'promoters',
        ] as $t) {
            if (Schema::hasTable($t)) {
                DB::table($t)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->seedCitiesAndBranches();
        $this->seedRolesAndUsers();
        $this->seedPromoters();
        $this->call(RoutesSeeder::class);
        $this->seedTemplates();
        $this->seedAdResiduals();
        $this->seedRouteActions();
        $this->seedInterviews();
        $this->seedPromoterSalaries();
        $this->seedSalaryAdjustments();
        $this->seedInstructions();
    }

    private function seedRolesAndUsers(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('users')) return;

        // roles: без created_at/updated_at (их нет)
        $roleIds = [];
        foreach ([
            'developer',
            'general_director',
            'regional_director',
            'branch_director',
            'manager',
            'promoter',
        ] as $roleName) {
            $roleIds[$roleName] = DB::table('roles')->insertGetId(['role_name' => $roleName], 'role_id');
        }

        // role_module_access
        if (Schema::hasTable('role_module_access')) {
            $cols = $this->tableColumns('role_module_access');

            // у тебя обязательное поле module_code
            $moduleField = null;
            if (in_array('module_code', $cols, true)) $moduleField = 'module_code';
            elseif (in_array('module_key', $cols, true)) $moduleField = 'module_key';

            $modules = [
                'promoters',
                'route_actions',
                'cards',
                'interviews',
                'salary',
                'reports',
                'routes',
                'ad_templates',
                'ad_residuals',
                'instructions',
                'keys_registry',
            ];

            $rows = [];
            if ($moduleField) {
                $fullAccessRoles = [
                    $roleIds['developer'],
                    $roleIds['general_director'],
                    $roleIds['regional_director'],
                    $roleIds['branch_director'],
                ];

                $instructionRestricted = [
                    $roleIds['regional_director'],
                    $roleIds['branch_director'],
                ];

                foreach ($fullAccessRoles as $rid) {
                    foreach ($modules as $m) {
                        $row = [];

                        if (in_array('role_id', $cols, true)) $row['role_id'] = $rid;
                        $row[$moduleField] = $m;

                        $instructionOnly = $m === 'instructions' && in_array($rid, $instructionRestricted, true);

                        if (in_array('can_view', $cols, true)) $row['can_view'] = $instructionOnly ? 0 : 1;
                        if (in_array('can_add', $cols, true)) $row['can_add'] = $instructionOnly ? 0 : 1;
                        if (in_array('can_edit', $cols, true)) $row['can_edit'] = $instructionOnly ? 0 : 1;
                        if (in_array('can_delete', $cols, true)) $row['can_delete'] = $instructionOnly ? 0 : 1;

                        if (in_array('created_at', $cols, true)) $row['created_at'] = now();
                        if (in_array('updated_at', $cols, true)) $row['updated_at'] = now();

                        $rows[] = $row;
                    }
                }

                foreach ($modules as $m) {
                    $row = [];

                    if (in_array('role_id', $cols, true)) $row['role_id'] = $roleIds['manager'];
                    $row[$moduleField] = $m;

                    if (in_array('can_view', $cols, true)) {
                        $row['can_view'] = in_array($m, ['ad_templates', 'ad_residuals', 'instructions'], true) ? 0 : 1;
                    }
                    if (in_array('can_add', $cols, true)) $row['can_add'] = 1;
                    if (in_array('can_edit', $cols, true)) {
                        $row['can_edit'] = in_array($m, ['salary', 'ad_templates', 'ad_residuals', 'instructions'], true) ? 0 : 1;
                    }
                    if (in_array('can_delete', $cols, true)) $row['can_delete'] = 1;

                    if (in_array('created_at', $cols, true)) $row['created_at'] = now();
                    if (in_array('updated_at', $cols, true)) $row['updated_at'] = now();

                    $rows[] = $row;
                }

                foreach (['salary', 'route_actions'] as $m) {
                    $row = [];

                    if (in_array('role_id', $cols, true)) $row['role_id'] = $roleIds['promoter'];
                    $row[$moduleField] = $m;

                    if (in_array('can_view', $cols, true)) $row['can_view'] = 1;
                    if (in_array('can_add', $cols, true)) $row['can_add'] = 0;
                    if (in_array('can_edit', $cols, true)) $row['can_edit'] = $m === 'salary' ? 1 : 0;
                    if (in_array('can_delete', $cols, true)) $row['can_delete'] = 0;

                    if (in_array('created_at', $cols, true)) $row['created_at'] = now();
                    if (in_array('updated_at', $cols, true)) $row['updated_at'] = now();

                    $rows[] = $row;
                }

                DB::table('role_module_access')->insert($rows);
            }
        }

        // users: developer/manager как на форме логина
        $userCols = $this->tableColumns('users');

        $developerRow = [];
        if (in_array('user_login', $userCols, true)) $developerRow['user_login'] = 'developer';
        if (in_array('user_full_name', $userCols, true)) $developerRow['user_full_name'] = 'Developer';
        if (in_array('role_id', $userCols, true)) $developerRow['role_id'] = $roleIds['developer'];
        if (in_array('user_password_hash', $userCols, true)) $developerRow['user_password_hash'] = Hash::make('developer12345');
        if (in_array('password', $userCols, true)) $developerRow['password'] = Hash::make('developer12345');
        if (in_array('created_at', $userCols, true)) $developerRow['created_at'] = now();
        if (in_array('updated_at', $userCols, true)) $developerRow['updated_at'] = now();

        $managerRow = [];
        if (in_array('user_login', $userCols, true)) $managerRow['user_login'] = 'manager';
        if (in_array('user_full_name', $userCols, true)) $managerRow['user_full_name'] = 'Manager';
        if (in_array('role_id', $userCols, true)) $managerRow['role_id'] = $roleIds['manager'];
        if (in_array('user_password_hash', $userCols, true)) $managerRow['user_password_hash'] = Hash::make('manager12345');
        if (in_array('password', $userCols, true)) $managerRow['password'] = Hash::make('manager12345');
        if (in_array('created_at', $userCols, true)) $managerRow['created_at'] = now();
        if (in_array('updated_at', $userCols, true)) $managerRow['updated_at'] = now();

        DB::table('users')->insert([$developerRow, $managerRow]);

        if (Schema::hasColumn('users', 'branch_id') && Schema::hasTable('branches')) {
            $branchId = DB::table('branches')->value('branch_id');
            if ($branchId) {
                DB::table('users')
                    ->where('user_login', 'manager')
                    ->update(['branch_id' => $branchId]);
            }
        }

        if (Schema::hasColumn('users', 'city_id') && Schema::hasTable('cities')) {
            $cityId = DB::table('cities')->value('city_id');
            if ($cityId) {
                DB::table('users')
                    ->where('user_login', 'manager')
                    ->update(['city_id' => $cityId]);
            }
        }
    }

    private function seedCitiesAndBranches(): void
    {
        if (!Schema::hasTable('cities') || !Schema::hasTable('branches')) return;

        $cityRows = [
            ['city_name' => 'Новосибирск', 'region_name' => 'Новосибирская область', 'population' => 1625631],
            ['city_name' => 'Краснодар', 'region_name' => 'Краснодарский край', 'population' => 1035669],
            ['city_name' => 'Москва', 'region_name' => 'Москва', 'population' => 13010112],
        ];

        foreach ($cityRows as $row) {
            DB::table('cities')->insert(array_merge($row, [
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $cities = DB::table('cities')->pluck('city_id', 'city_name');

        $branchRows = [
            [
                'branch_name' => 'Филиал Новосибирск',
                'city_id' => $cities['Новосибирск'] ?? $cities->first(),
            ],
            [
                'branch_name' => 'Филиал Краснодар',
                'city_id' => $cities['Краснодар'] ?? $cities->first(),
            ],
            [
                'branch_name' => 'Филиал Москва',
                'city_id' => $cities['Москва'] ?? $cities->first(),
            ],
        ];

        foreach ($branchRows as $row) {
            DB::table('branches')->insert(array_merge($row, [
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function seedPromoters(): void
    {
        if (!Schema::hasTable('promoters')) return;

        $rows = [
            [
                'promoter_full_name' => 'Иван Петров',
                'promoter_phone' => '+7 999 111-22-33',
                'promoter_status' => 'active',
                'hired_at' => now()->subMonths(2)->format('Y-m-d'),
            ],
            [
                'promoter_full_name' => 'Мария Сидорова',
                'promoter_phone' => '+7 999 222-33-44',
                'promoter_status' => 'active',
                'hired_at' => now()->subMonths(3)->format('Y-m-d'),
            ],
        ];

        $branchId = Schema::hasColumn('promoters', 'branch_id')
            ? DB::table('branches')->value('branch_id')
            : null;

        foreach ($rows as $row) {
            if ($branchId) {
                $row['branch_id'] = $branchId;
            }
            DB::table('promoters')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function seedTemplates(): void
    {
        if (!Schema::hasTable('ad_templates')) return;

        $templates = [
            ['template_name' => 'Листовка A5', 'template_type' => 'leaflet', 'is_active' => 1],
            ['template_name' => 'Листовка A6', 'template_type' => 'leaflet', 'is_active' => 1],
        ];

        foreach ($templates as $template) {
            DB::table('ad_templates')->insert(array_merge($template, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function seedAdResiduals(): void
    {
        if (!Schema::hasTable('ad_residuals') || !Schema::hasTable('branches')) return;

        $branches = DB::table('branches')->pluck('branch_id');

        $rows = [];
        foreach ($branches as $branchId) {
            $rows[] = [
                'branch_id' => $branchId,
                'ad_type' => 'leaflet',
                'ad_amount' => 1000,
                'remaining_amount' => 400,
                'received_at' => now()->subDays(10)->format('Y-m-d'),
                'notes' => 'Партия 1',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('ad_residuals')->insert($rows);
    }

    private function seedRouteActions(): void
    {
        if (!Schema::hasTable('route_actions') || !Schema::hasTable('routes') || !Schema::hasTable('promoters')) return;

        $routeId = DB::table('routes')->value('route_id');
        $promoterId = DB::table('promoters')->value('promoter_id');

        if (!$routeId || !$promoterId) return;

        DB::table('route_actions')->insert([
            'action_date' => now()->subDays(2)->format('Y-m-d'),
            'promoter_id' => $promoterId,
            'route_id' => $routeId,
            'leaflets_total' => 500,
            'leaflets_issued' => 300,
            'posters_total' => 100,
            'posters_issued' => 60,
            'cards_count' => 200,
            'cards_issued' => 150,
            'boxes_done' => 5,
            'payment_amount' => 1500,
            'created_by' => DB::table('users')->value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedInterviews(): void
    {
        if (!Schema::hasTable('interviews')) return;

        DB::table('interviews')->insert([
            'interview_date' => now()->addDays(2)->format('Y-m-d'),
            'candidate_name' => 'Павел Новиков',
            'candidate_phone' => '+7 999 555-66-77',
            'source' => 'hh.ru',
            'status' => 'planned',
            'comment' => 'Первичное собеседование',
            'created_by' => DB::table('users')->value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPromoterSalaries(): void
    {
        if (!Schema::hasTable('promoter_salaries')) return;
    }

    private function seedSalaryAdjustments(): void
    {
        if (!Schema::hasTable('salary_adjustments') || !Schema::hasTable('promoters')) return;

        $promoterId = DB::table('promoters')->value('promoter_id');
        if (!$promoterId) return;

        DB::table('salary_adjustments')->insert([
            'promoter_id' => $promoterId,
            'adj_date' => now()->subDays(1)->format('Y-m-d'),
            'amount' => 500,
            'comment' => 'Премия',
            'created_by' => DB::table('users')->value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedInstructions(): void
    {
        if (!Schema::hasTable('instructions')) return;

        DB::table('instructions')->insert([
            'title' => 'Инструкция по раздаче',
            'body' => 'Проверяйте количество листовок перед выходом.',
            'created_by' => DB::table('users')->value('id'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function tableColumns(string $table): array
    {
        return Schema::getColumnListing($table);
    }
}
