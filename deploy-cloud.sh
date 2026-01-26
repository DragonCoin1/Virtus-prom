#!/bin/bash

# Скрипт автоматического деплоя Virtus-prom на облачный сервер
# Использование: ./deploy-cloud.sh

set -e  # Остановка при ошибке

echo "🚀 Начало деплоя Virtus-prom..."

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

echo -e "${YELLOW}📦 Шаг 1: Установка PHP зависимостей...${NC}"
composer install --optimize-autoloader --no-dev --no-interaction

echo -e "${YELLOW}📦 Шаг 2: Установка Node.js зависимостей...${NC}"
npm install

echo -e "${YELLOW}🔨 Шаг 3: Сборка фронтенда...${NC}"
npm run build

echo -e "${YELLOW}⚙️  Шаг 4: Проверка .env файла...${NC}"
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  .env файл не найден. Создаю из .env.example...${NC}"
    cp .env.example .env
    
    if [ -z "$APP_KEY" ]; then
        echo -e "${YELLOW}🔑 Генерация ключа приложения...${NC}"
        php artisan key:generate
    fi
    
    echo -e "${RED}⚠️  ВАЖНО: Отредактируйте .env файл с правильными настройками!${NC}"
    echo -e "${YELLOW}Нажмите Enter после редактирования .env файла...${NC}"
    read
fi

echo -e "${YELLOW}🗄️  Шаг 5: Запуск миграций...${NC}"
php artisan migrate --force

echo -e "${YELLOW}📁 Шаг 6: Настройка прав доступа...${NC}"
# Создание необходимых директорий
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Установка прав (если запущено от root, меняем владельца)
if [ "$EUID" -eq 0 ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi
chmod -R 775 storage bootstrap/cache

echo -e "${YELLOW}⚡ Шаг 7: Оптимизация для продакшн...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize

echo -e "${GREEN}✅ Деплой завершен успешно!${NC}"
echo -e "${GREEN}🌐 Приложение готово к работе!${NC}"
