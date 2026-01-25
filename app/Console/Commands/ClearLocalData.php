<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearLocalData extends Command
{
    protected $signature = 'data:clear-local {--force : Выполнить без подтверждения}';
    protected $description = 'Очищает все локальные данные (промоутеры, собесы, разноска, зарплата, остатки рекламы, инструкции), но сохраняет пользователей и справочники';

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Вы уверены, что хотите удалить ВСЕ локальные данные? Это действие необратимо!')) {
            $this->info('Операция отменена.');
            return 0;
        }

        $this->info('Начинаю очистку данных...');

        // Список таблиц для очистки
        $tables = [
            'route_action_templates' => 'Связи разноски с шаблонами',
            'route_actions' => 'Разноска',
            'routes' => 'Маршруты',
            'salary_adjustments' => 'Корректировки зарплаты',
            'ad_residuals' => 'Остатки рекламы',
            'interviews' => 'Собеседования',
            'instructions' => 'Инструкции',
            'promoters' => 'Промоутеры',
        ];

        // Отключаем проверку внешних ключей для возможности TRUNCATE
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $deleted = 0;

        foreach ($tables as $table => $description) {
            try {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $count = DB::table($table)->count();
                    if ($count > 0) {
                        DB::table($table)->truncate();
                        $this->info("✓ {$description} ({$table}): удалено {$count} записей");
                        $deleted += $count;
                    } else {
                        $this->line("  {$description} ({$table}): таблица пуста");
                    }
                } else {
                    $this->warn("  Таблица {$table} не найдена, пропускаю");
                }
            } catch (\Exception $e) {
                $this->error("  Ошибка при очистке {$table}: " . $e->getMessage());
            }
        }

        // Включаем обратно проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->newLine();
        $this->info("Готово! Удалено записей: {$deleted}");
        $this->info('Сохранены: пользователи, города, филиалы, шаблоны рекламы');

        return 0;
    }
}
