-- Скрипт для очистки тестовых данных
-- ВНИМАНИЕ: Удаляет все данные из указанных таблиц!
-- Сохраняет: users, cities, branches, routes, ad_templates, roles, user_cities

SET FOREIGN_KEY_CHECKS = 0;

-- Очистка таблиц (в порядке зависимостей)
TRUNCATE TABLE `route_action_templates`;
TRUNCATE TABLE `route_actions`;
TRUNCATE TABLE `routes`;
TRUNCATE TABLE `salary_adjustments`;
TRUNCATE TABLE `ad_residuals`;
TRUNCATE TABLE `interviews`;
TRUNCATE TABLE `instructions`;
TRUNCATE TABLE `promoters`;

SET FOREIGN_KEY_CHECKS = 1;

-- Проверка результатов
SELECT 
    'route_action_templates' as table_name, COUNT(*) as count FROM route_action_templates
UNION ALL
SELECT 'route_actions', COUNT(*) FROM route_actions
UNION ALL
SELECT 'routes', COUNT(*) FROM routes
UNION ALL
SELECT 'salary_adjustments', COUNT(*) FROM salary_adjustments
UNION ALL
SELECT 'ad_residuals', COUNT(*) FROM ad_residuals
UNION ALL
SELECT 'interviews', COUNT(*) FROM interviews
UNION ALL
SELECT 'instructions', COUNT(*) FROM instructions
UNION ALL
SELECT 'promoters', COUNT(*) FROM promoters;
