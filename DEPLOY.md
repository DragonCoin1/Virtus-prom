# Инструкция по деплою Virtus-prom

> 💡 **Для деплоя на облачный сервер** см. файл [DEPLOY-CLOUD.md](./DEPLOY-CLOUD.md)

## Вариант 1: Деплой через Laragon (локально)

### Шаг 1: Проверка окружения
Убедитесь, что Laragon запущен и все сервисы активны:
- Apache/Nginx
- MySQL/MariaDB (если используете MySQL вместо SQLite)
- PHP 8.2+

### Шаг 2: Настройка базы данных

#### Если используете SQLite (по умолчанию):
Убедитесь, что файл `database/database.sqlite` существует:
```bash
# Если файла нет, создайте его
touch database/database.sqlite
```

#### Если используете MySQL:
1. Создайте базу данных через phpMyAdmin или командную строку:
```sql
CREATE DATABASE virtus_prom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Обновите `.env` файл:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=virtus_prom
DB_USERNAME=root
DB_PASSWORD=
```

### Шаг 3: Установка зависимостей

Откройте терминал в корне проекта и выполните:

```bash
# Установка PHP зависимостей
composer install --optimize-autoloader --no-dev

# Установка Node.js зависимостей
npm install

# Сборка фронтенда для продакшн
npm run build
```

### Шаг 4: Настройка .env файла

1. Скопируйте `.env.example` в `.env` (если еще не сделано):
```bash
copy .env.example .env
```

2. Сгенерируйте ключ приложения:
```bash
php artisan key:generate
```

3. Настройте `.env` для продакшн:
```env
APP_NAME="Virtus Prom"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://virtus-prom.test  # или ваш домен

# Для продакшн лучше использовать MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=virtus_prom
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### Шаг 5: Запуск миграций

```bash
# Запуск миграций
php artisan migrate --force

# (Опционально) Заполнение тестовыми данными
php artisan db:seed
```

### Шаг 6: Настройка прав доступа

```bash
# Создание необходимых директорий
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Установка прав (для Linux/Mac)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Шаг 7: Оптимизация для продакшн

```bash
# Кэширование конфигурации
php artisan config:cache

# Кэширование маршрутов
php artisan route:cache

# Кэширование представлений
php artisan view:cache

# Оптимизация автозагрузчика
composer dump-autoload --optimize
```

### Шаг 8: Настройка виртуального хоста в Laragon

1. Откройте Laragon
2. Нажмите "Menu" → "Tools" → "Quick add" → "Virtual Host"
3. Введите имя домена (например: `virtus-prom.test`)
4. Laragon автоматически создаст виртуальный хост

Или создайте вручную файл `C:\laragon\etc\apache2\sites-enabled\virtus-prom.test.conf`:

```apache
<VirtualHost *:80>
    ServerName virtus-prom.test
    DocumentRoot "C:/laragon/www/Virtus-prom/public"
    
    <Directory "C:/laragon/www/Virtus-prom/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Шаг 9: Перезапуск сервера

В Laragon нажмите "Stop All" и затем "Start All"

### Шаг 10: Проверка

Откройте в браузере: `http://virtus-prom.test`

---

## Вариант 2: Деплой на продакшн сервер (Linux)

### Требования
- Ubuntu 20.04+ / Debian 11+ / CentOS 8+
- PHP 8.2+ с расширениями: mbstring, xml, curl, zip, pdo, pdo_mysql
- Composer
- Node.js 18+ и npm
- MySQL 8.0+ или PostgreSQL
- Nginx или Apache
- SSL сертификат (Let's Encrypt)

### Шаг 1: Подготовка сервера

```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка необходимых пакетов
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath nginx mysql-server \
    git curl unzip

# Установка Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Установка Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

### Шаг 2: Клонирование проекта

```bash
cd /var/www
sudo git clone <ваш-репозиторий> Virtus-prom
cd Virtus-prom
sudo chown -R www-data:www-data /var/www/Virtus-prom
```

### Шаг 3: Установка зависимостей

```bash
# PHP зависимости
composer install --optimize-autoloader --no-dev

# Node.js зависимости
npm install

# Сборка фронтенда
npm run build
```

### Шаг 4: Настройка базы данных

```bash
# Вход в MySQL
sudo mysql -u root -p

# Создание базы данных
CREATE DATABASE virtus_prom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'virtus_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON virtus_prom.* TO 'virtus_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Шаг 5: Настройка .env

```bash
cp .env.example .env
nano .env
```

Настройте следующие параметры:
```env
APP_NAME="Virtus Prom"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=virtus_prom
DB_USERNAME=virtus_user
DB_PASSWORD=strong_password_here

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

```bash
# Генерация ключа
php artisan key:generate
```

### Шаг 6: Запуск миграций

```bash
php artisan migrate --force
```

### Шаг 7: Настройка прав

```bash
sudo chown -R www-data:www-data /var/www/Virtus-prom
sudo chmod -R 755 /var/www/Virtus-prom
sudo chmod -R 775 /var/www/Virtus-prom/storage
sudo chmod -R 775 /var/www/Virtus-prom/bootstrap/cache
```

### Шаг 8: Настройка Nginx

Создайте файл `/etc/nginx/sites-available/virtus-prom`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/Virtus-prom/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Активируйте конфигурацию:
```bash
sudo ln -s /etc/nginx/sites-available/virtus-prom /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Шаг 9: Настройка SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Шаг 10: Оптимизация для продакшн

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize
```

### Шаг 11: Настройка очередей (опционально)

Если используете очереди, настройте Supervisor:

```bash
sudo apt install supervisor
```

Создайте файл `/etc/supervisor/conf.d/virtus-prom-worker.conf`:

```ini
[program:virtus-prom-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/Virtus-prom/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/Virtus-prom/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start virtus-prom-worker:*
```

### Шаг 12: Настройка cron для планировщика задач

```bash
sudo crontab -e -u www-data
```

Добавьте строку:
```
* * * * * cd /var/www/Virtus-prom && php artisan schedule:run >> /dev/null 2>&1
```

---

## Полезные команды для обслуживания

### Очистка кэша
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Пересоздание кэша
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Обновление после деплоя
```bash
git pull
composer install --optimize-autoloader --no-dev
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Проверка логов
```bash
tail -f storage/logs/laravel.log
```

---

## Безопасность

1. **Никогда не коммитьте `.env` файл в Git**
2. Установите `APP_DEBUG=false` в продакшн
3. Используйте сильные пароли для базы данных
4. Настройте файрвол:
   ```bash
   sudo ufw allow 22
   sudo ufw allow 80
   sudo ufw allow 443
   sudo ufw enable
   ```
5. Регулярно обновляйте зависимости:
   ```bash
   composer update
   npm update
   ```

---

## Решение проблем

### Ошибка 500
- Проверьте права доступа к `storage/` и `bootstrap/cache/`
- Проверьте логи: `storage/logs/laravel.log`
- Убедитесь, что `.env` настроен правильно

### Ошибка "No application encryption key"
```bash
php artisan key:generate
```

### Ошибка подключения к базе данных
- Проверьте настройки в `.env`
- Убедитесь, что база данных создана
- Проверьте права пользователя БД

### Статические файлы не загружаются
- Убедитесь, что выполнили `npm run build`
- Проверьте, что `public/build/` существует
- Очистите кэш: `php artisan view:clear`
