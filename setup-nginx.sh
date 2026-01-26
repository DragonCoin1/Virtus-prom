#!/bin/bash

# Скрипт автоматической настройки Nginx для Virtus-prom
# Использование: ./setup-nginx.sh

set -e

echo "🌐 Настройка Nginx для Virtus-prom..."

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Получаем путь к проекту
PROJECT_PATH="/home/user1/Amir/Virtus-prom"
SERVER_IP="95.174.94.60"

# Проверяем, что Nginx установлен
if ! command -v nginx &> /dev/null; then
    echo -e "${YELLOW}📦 Установка Nginx...${NC}"
    sudo apt update
    sudo apt install -y nginx
fi

# Создаем конфигурацию Nginx
echo -e "${YELLOW}⚙️  Создание конфигурации Nginx...${NC}"
sudo tee /etc/nginx/sites-available/virtus-prom > /dev/null <<EOF
server {
    listen 80;
    server_name ${SERVER_IP} _;
    root ${PROJECT_PATH}/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    # Максимальный размер загружаемых файлов
    client_max_body_size 20M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Активируем конфигурацию
echo -e "${YELLOW}🔗 Активация конфигурации...${NC}"
sudo ln -sf /etc/nginx/sites-available/virtus-prom /etc/nginx/sites-enabled/

# Удаляем дефолтный сайт (опционально)
if [ -f /etc/nginx/sites-enabled/default ]; then
    echo -e "${YELLOW}🗑️  Удаление дефолтного сайта...${NC}"
    sudo rm /etc/nginx/sites-enabled/default
fi

# Проверяем конфигурацию
echo -e "${YELLOW}✅ Проверка конфигурации Nginx...${NC}"
if sudo nginx -t; then
    echo -e "${GREEN}✅ Конфигурация корректна!${NC}"
else
    echo -e "${RED}❌ Ошибка в конфигурации!${NC}"
    exit 1
fi

# Перезагружаем Nginx
echo -e "${YELLOW}🔄 Перезагрузка Nginx...${NC}"
sudo systemctl reload nginx

# Настраиваем файрвол
echo -e "${YELLOW}🔥 Настройка файрвола...${NC}"
sudo ufw allow 80/tcp 2>/dev/null || true
sudo ufw allow 443/tcp 2>/dev/null || true

# Настраиваем .env если нужно
if [ ! -f "${PROJECT_PATH}/.env" ]; then
    echo -e "${YELLOW}⚙️  Создание .env файла...${NC}"
    cp "${PROJECT_PATH}/.env.example" "${PROJECT_PATH}/.env"
    php "${PROJECT_PATH}/artisan" key:generate
fi

# Обновляем APP_URL в .env
if grep -q "APP_URL=" "${PROJECT_PATH}/.env"; then
    sed -i "s|APP_URL=.*|APP_URL=http://${SERVER_IP}|g" "${PROJECT_PATH}/.env"
else
    echo "APP_URL=http://${SERVER_IP}" >> "${PROJECT_PATH}/.env"
fi

# Очищаем и пересоздаем кэш
echo -e "${YELLOW}🧹 Обновление кэша...${NC}"
php "${PROJECT_PATH}/artisan" config:clear
php "${PROJECT_PATH}/artisan" config:cache

echo -e "${GREEN}✅ Настройка завершена!${NC}"
echo -e "${GREEN}🌐 Сайт доступен по адресу: http://${SERVER_IP}${NC}"
