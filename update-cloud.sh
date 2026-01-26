#!/bin/bash

# Скрипт обновления Virtus-prom на облачном сервере
# Использование: ./update-cloud.sh

set -e  # Остановка при ошибке

echo "🔄 Начало обновления Virtus-prom..."

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Проверка, что мы в правильной директории
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Ошибка: файл artisan не найден. Убедитесь, что вы в корне проекта.${NC}"
    exit 1
fi

echo -e "${YELLOW}📥 Получение обновлений из Git...${NC}"
git pull

echo -e "${YELLOW}📦 Обновление PHP зависимостей...${NC}"
composer install --optimize-autoloader --no-dev --no-interaction

echo -e "${YELLOW}📦 Обновление Node.js зависимостей...${NC}"
npm install

echo -e "${YELLOW}🔨 Сборка фронтенда...${NC}"
npm run build

echo -e "${YELLOW}🗄️  Запуск миграций...${NC}"
php artisan migrate --force

echo -e "${YELLOW}🧹 Очистка кэша...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo -e "${YELLOW}⚡ Оптимизация для продакшн...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize

echo -e "${GREEN}✅ Обновление завершено успешно!${NC}"
