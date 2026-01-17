<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FullTestSeeder extends Seeder
{
    public function run(): void
    {
        // Аккуратно чистим тестовые данные (если таблицы есть)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
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

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 1) Промоутеры
        $this->seedPromoters();

        // 2) Маршруты
        $this->call(RoutesSeeder::class);

        // 3) Макеты
        $this->seedTemplates();

        // 4) Разноска (route_actions) + pivot templates
        $this->seedRouteActions();

        // 5) Собеседования (если таблица есть)
        $this->seedInterviews();

        // 6) Корректировки зарплаты (если таблица есть)
        $this->seedSalaryAdjustments();
    }

    private function seedPromoters(): void
    {
        if (!Schema::hasTable('promoters')) return;

        $statuses = ['active', 'trainee', 'paused'];

        $rows = [];
        for ($i = 1; $i <= 18; $i++) {
            $name = $this->fakeRuName($i);
            $phone = '+79' . random_int(100000000, 999999999);

            $hired = Carbon::now()->subDays(random_int(5, 120))->format('Y-m-d');

            $status = $statuses[array_rand($statuses)];
            $firedAt = null;

            // часть делаем уволенными
            if ($i % 7 === 0) {
                $firedAt = Carbon::now()->subDays(random_int(1, 30))->format('Y-m-d');
                $status = 'fired';
            }

            $rows[] = [
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
            if ($hasActive) $row['is_active'] = ($idx === 3) ? 0 : 1; // одну выключим для теста
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
            $templateIds = DB::table('ad_templates')->where('is_active', 1)->pluck('template_id')->toArray();
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

                // pivot макетов (если есть pivot и есть активные макеты)
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

    private function seedInterviews(): void
    {
        if (!Schema::hasTable('interviews')) return;

        $statuses = ['planned', 'came', 'no_show', 'hired', 'rejected'];
        $today = Carbon::now()->startOfDay();

        $rows = [];
        for ($i = 1; $i <= 18; $i++) {
            $rows[] = [
                'interview_date' => $today->copy()->subDays(random_int(0, 20))->format('Y-m-d'),
                'candidate_name' => 'Кандидат ' . $i,
                'candidate_phone' => '+79' . random_int(100000000, 999999999),
                'source' => ['avito', 'hh', 'знакомые'][array_rand(['avito', 'hh', 'знакомые'])],
                'status' => $statuses[array_rand($statuses)],
                'comment' => (random_int(1, 6) === 1) ? 'тестовый коммент' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('interviews')->insert($rows);
    }

    private function seedSalaryAdjustments(): void
    {
        if (!Schema::hasTable('salary_adjustments')) return;
        if (!Schema::hasTable('promoters')) return;

        $promoters = DB::table('promoters')->pluck('promoter_id')->toArray();
        if (empty($promoters)) return;

        $today = Carbon::now()->startOfDay();

        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $amount = [200, 300, -150, -200, 500][array_rand([200, 300, -150, -200, 500])];
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
}
