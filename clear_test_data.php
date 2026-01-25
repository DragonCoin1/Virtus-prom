<?php

/**
 * Скрипт для очистки тестовых данных
 * Запуск: php clear_test_data.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Очистка тестовых данных ===\n\n";

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

$totalDeleted = 0;

foreach ($tables as $table => $description) {
    try {
        if (DB::getSchemaBuilder()->hasTable($table)) {
            $count = DB::table($table)->count();
            if ($count > 0) {
                DB::table($table)->truncate();
                echo "✓ {$description} ({$table}): удалено {$count} записей\n";
                $totalDeleted += $count;
            } else {
                echo "  {$description} ({$table}): таблица уже пуста\n";
            }
        } else {
            echo "  Таблица {$table} не найдена, пропускаю\n";
        }
    } catch (\Exception $e) {
        echo "  ✗ Ошибка при очистке {$table}: " . $e->getMessage() . "\n";
    }
}

// Включаем обратно проверку внешних ключей
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "\n=== Готово! ===\n";
echo "Всего удалено записей: {$totalDeleted}\n";
echo "Сохранены: пользователи, города, филиалы, шаблоны рекламы\n";
