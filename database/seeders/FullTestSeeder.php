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

            $modules = ['promoters','route_actions','cards','interviews','salary','reports','routes','ad_templates','keys_registry'];

            $rows = [];
            if ($moduleField) {
                $fullAccessRoles = [
                    $roleIds['developer'],
                    $roleIds['general_director'],
                    $roleIds['regional_director'],
                    $roleIds['branch_director'],
                ];

                foreach ($fullAccessRoles as $rid) {
                    foreach ($modules as $m) {
                        $row = [];

                        if (in_array('role_id', $cols, true)) $row['role_id'] = $rid;
                        $row[$moduleField] = $m;

                        if (in_array('can_view', $cols, true)) $row['can_view'] = 1;
                        if (in_array('can_add', $cols, true)) $row['can_add'] = 1;
                        if (in_array('can_edit', $cols, true)) $row['can_edit'] = 1;
                        if (in_array('can_delete', $cols, true)) $row['can_delete'] = 1;

                        if (in_array('created_at', $cols, true)) $row['created_at'] = now();
                        if (in_array('updated_at', $cols, true)) $row['updated_at'] = now();

                        $rows[] = $row;
                    }
                }

                foreach ($modules as $m) {
                    $row = [];

                    if (in_array('role_id', $cols, true)) $row['role_id'] = $roleIds['manager'];
                    $row[$moduleField] = $m;

                    if (in_array('can_view', $cols, true)) $row['can_view'] = $m === 'ad_templates' ? 0 : 1;
                    if (in_array('can_add', $cols, true)) $row['can_add'] = 1;
                    if (in_array('can_edit', $cols, true)) $row['can_edit'] = in_array($m, ['salary', 'ad_templates'], true) ? 0 : 1;
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

        $branchId = null;
        if (Schema::hasColumn('promoters', 'branch_id') && Schema::hasTable('branches')) {
            $branchId = DB::table('branches')->value('branch_id');
        }

        $statuses = ['active', 'trainee', 'paused'];

        $rows = [];
        for ($i = 1; $i <= 18; $i++) {
            $name = $this->fakeRuName($i);
            $phone = '+79' . random_int(100000000, 999999999);

            $hired = Carbon::now()->subDays(random_int(5, 120))->format('Y-m-d');

            $status = $statuses[array_rand($statuses)];
            $firedAt = null;

            if ($i % 7 === 0) {
                $firedAt = Carbon::now()->subDays(random_int(1, 30))->format('Y-m-d');
                $status = 'fired';
            }

            $rows[] = [
                'branch_id' => $branchId,
                'promoter_full_name' => $name,
                'promoter_phone' => $phone,
                'promoter_status' => $status,
                'hired_at' => $hired,
                'fired_at' => $firedAt,
                'promoter_comment' => ($i % 5 === 0) ? 'тестовый комментарий' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('promoters')->insert($rows);
    }

    private function seedTemplates(): void
    {
        if (!Schema::hasTable('ad_templates')) return;

        $hasActive = Schema::hasColumn('ad_templates', 'is_active');
        $hasType = Schema::hasColumn('ad_templates', 'template_type');

        $rows = [];
        $names = [
            'Листовка А (основная)',
            'Листовка B (акция)',
            'Листовка C (новый район)',
            'Листовка D (ночная)',
            'Листовка E (премиум)',
        ];

        foreach ($names as $idx => $n) {
            $row = [
                'template_name' => $n,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($hasType) $row['template_type'] = 'leaflet';
            if ($hasActive) $row['is_active'] = ($idx === 3) ? 0 : 1;
            $rows[] = $row;
        }

        DB::table('ad_templates')->insert($rows);
    }

    private function seedRouteActions(): void
    {
        if (!Schema::hasTable('route_actions')) return;
        if (!Schema::hasTable('promoters') || !Schema::hasTable('routes')) return;

        $promoters = DB::table('promoters')->pluck('promoter_id')->toArray();
        $routes = DB::table('routes')->pluck('route_id')->toArray();

        if (empty($promoters) || empty($routes)) return;

        $hasIssuedLeaflets = Schema::hasColumn('route_actions', 'leaflets_issued');
        $hasIssuedPosters = Schema::hasColumn('route_actions', 'posters_issued');
        $hasIssuedCards = Schema::hasColumn('route_actions', 'cards_issued');

        $templateIds = [];
        if (Schema::hasTable('ad_templates')) {
            if (Schema::hasColumn('ad_templates', 'is_active')) {
                $templateIds = DB::table('ad_templates')->where('is_active', 1)->pluck('template_id')->toArray();
            } else {
                $templateIds = DB::table('ad_templates')->pluck('template_id')->toArray();
            }
        }

        $today = Carbon::now()->startOfDay();

        for ($d = 0; $d < 25; $d++) {
            $date = $today->copy()->subDays($d)->format('Y-m-d');
            $rowsCount = random_int(1, 5);

            for ($k = 0; $k < $rowsCount; $k++) {
                $leaflets = random_int(0, 250);
                $boxes = random_int(0, 8);
                $posters = random_int(0, 60);
                $cards = random_int(0, 80);

                $pay = ($boxes * 120) + (int)($leaflets * 0.8) + (int)($posters * 3) + (int)($cards * 0.5);

                $insert = [
                    'action_date' => $date,
                    'promoter_id' => $promoters[array_rand($promoters)],
                    'route_id' => $routes[array_rand($routes)],
                    'leaflets_total' => $leaflets,
                    'posters_total' => $posters,
                    'cards_count' => $cards,
                    'boxes_done' => $boxes,
                    'payment_amount' => $pay,
                    'action_comment' => (random_int(1, 10) === 1) ? 'тест' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($hasIssuedLeaflets) $insert['leaflets_issued'] = random_int(0, 300);
                if ($hasIssuedPosters) $insert['posters_issued'] = random_int(0, 80);
                if ($hasIssuedCards) $insert['cards_issued'] = random_int(0, 150);

                $id = DB::table('route_actions')->insertGetId($insert, 'route_action_id');

                if (Schema::hasTable('route_action_templates') && !empty($templateIds)) {
                    $take = random_int(0, 2);
                    if ($take > 0) {
                        $picked = $this->pickSome($templateIds, $take);
                        foreach ($picked as $tid) {
                            DB::table('route_action_templates')->insert([
                                'route_action_id' => $id,
                                'template_id' => $tid,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
    }

    private function seedAdResiduals(): void
    {
        if (!Schema::hasTable('ad_residuals') || !Schema::hasTable('branches')) return;

        $branchIds = DB::table('branches')->pluck('branch_id')->toArray();
        if (empty($branchIds)) return;

        $types = ['leaflet', 'card', 'poster', 'other'];
        $rows = [];

        foreach ($branchIds as $branchId) {
            $itemsCount = random_int(2, 4);
            for ($i = 0; $i < $itemsCount; $i++) {
                $amount = random_int(200, 1200);
                $remaining = random_int(0, $amount);
                $rows[] = [
                    'branch_id' => $branchId,
                    'ad_type' => $types[array_rand($types)],
                    'ad_amount' => $amount,
                    'remaining_amount' => $remaining,
                    'received_at' => Carbon::now()->subDays(random_int(1, 45))->format('Y-m-d'),
                    'notes' => (random_int(1, 4) === 1) ? 'тестовые остатки' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('ad_residuals')->insert($rows);
    }

    private function seedInterviews(): void
    {
        if (!Schema::hasTable('interviews')) return;

        $today = Carbon::now()->startOfDay();
        $statuses = ['planned', 'came', 'no_show', 'hired', 'rejected'];

        $hasNew = Schema::hasColumn('interviews', 'interview_candidate_name');
        $hasTime = Schema::hasColumn('interviews', 'interview_time');

        $rows = [];
        for ($i = 1; $i <= 18; $i++) {
            $date = $today->copy()->addDays(random_int(-2, 10))->format('Y-m-d');
            $time = sprintf('%02d:%02d', random_int(10, 19), [0, 15, 30, 45][array_rand([0, 15, 30, 45])]);

            if ($hasNew) {
                $row = [
                    'interview_date' => $date,
                    'interview_candidate_name' => 'Кандидат ' . $i,
                    'interview_phone' => '+79' . random_int(100000000, 999999999),
                    'interview_status' => $statuses[array_rand($statuses)],
                    'interview_comment' => (random_int(1, 6) === 1) ? 'тестовый коммент' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if ($hasTime) $row['interview_time'] = $time;
                $rows[] = $row;
            } else {
                $rows[] = [
                    'interview_date' => $date,
                    'candidate_name' => 'Кандидат ' . $i,
                    'candidate_phone' => '+79' . random_int(100000000, 999999999),
                    'source' => ['avito', 'hh', 'знакомые'][array_rand(['avito', 'hh', 'знакомые'])],
                    'status' => $statuses[array_rand($statuses)],
                    'comment' => (random_int(1, 6) === 1) ? 'тестовый коммент' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('interviews')->insert($rows);
    }

    private function seedPromoterSalaries(): void
    {
        if (!Schema::hasTable('promoter_salaries') || !Schema::hasTable('promoters')) return;

        $promoters = DB::table('promoters')->pluck('promoter_id')->toArray();
        if (empty($promoters)) return;

        $userId = null;
        if (Schema::hasTable('users') && Schema::hasColumn('promoter_salaries', 'created_by')) {
            $userId = DB::table('users')->value('id');
        }

        $rows = [];
        foreach ($promoters as $promoterId) {
            $rows[] = [
                'promoter_id' => $promoterId,
                'amount' => random_int(25000, 60000),
                'salary_period' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'created_by' => $userId,
                'comment' => (random_int(1, 5) === 1) ? 'тестовая ставка' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('promoter_salaries')->insert($rows);
    }

    private function seedSalaryAdjustments(): void
    {
        if (!Schema::hasTable('salary_adjustments')) return;
        if (!Schema::hasTable('promoters')) return;

        $promoters = DB::table('promoters')->pluck('promoter_id')->toArray();
        if (empty($promoters)) return;

        $today = Carbon::now()->startOfDay();
        $amountOptions = [200, 300, -150, -200, 500];

        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $amount = $amountOptions[array_rand($amountOptions)];
            $rows[] = [
                'promoter_id' => $promoters[array_rand($promoters)],
                'adj_date' => $today->copy()->subDays(random_int(0, 25))->format('Y-m-d'),
                'amount' => $amount,
                'comment' => ($amount < 0) ? 'штраф (тест)' : 'премия (тест)',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('salary_adjustments')->insert($rows);
    }

    private function seedInstructions(): void
    {
        if (!Schema::hasTable('instructions')) return;

        $creatorId = null;
        if (Schema::hasTable('users') && Schema::hasColumn('instructions', 'created_by')) {
            $creatorId = DB::table('users')->value('id');
        }

        $rows = [
            [
                'title' => 'Общие правила',
                'body' => 'Ведите отчёты ежедневно и обновляйте остатки рекламы.',
                'created_by' => $creatorId,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Работа с промоутерами',
                'body' => 'Назначайте ответственных и фиксируйте изменения по зарплате промоутеров.',
                'created_by' => $creatorId,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('instructions')->insert($rows);
    }

    private function pickSome(array $arr, int $count): array
    {
        $arr = array_values(array_unique($arr));
        shuffle($arr);
        return array_slice($arr, 0, max(0, $count));
    }

    private function fakeRuName(int $i): string
    {
        $first = ['Иван','Алексей','Дмитрий','Сергей','Максим','Антон','Артём','Никита','Павел','Егор','Кирилл','Роман'];
        $last = ['Иванов','Петров','Сидоров','Смирнов','Кузнецов','Попов','Васильев','Морозов','Волков','Соловьёв','Зайцев','Павлов'];
        return $last[$i % count($last)] . ' ' . $first[$i % count($first)];
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
