# Деплой Virtus-prom на облачный сервер

> 🌐 **Хотите сделать сайт доступным из интернета?** См. [PUBLISH-ONLINE.md](./PUBLISH-ONLINE.md)

## 🚀 Быстрый старт

### Вариант 1: Автоматический деплой (рекомендуется)

1. **Подключитесь к серверу по SSH:**
   ```bash
   ssh user@your-server-ip
   ```

2. **Клонируйте проект (если еще не клонирован):**
   ```bash
   cd /var/www
   git clone <ваш-репозиторий> Virtus-prom
   cd Virtus-prom
   ```

3. **Сделайте скрипт исполняемым и запустите:**
   ```bash
   chmod +x deploy-cloud.sh
   ./deploy-cloud.sh
   ```

4. **Настройте веб-сервер** (см. раздел ниже)

---

### Вариант 2: Ручной деплой

#### Шаг 1: Подготовка сервера

**Для Ubuntu/Debian:**
```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка необходимых пакетов
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-sqlite3 \
    nginx mysql-server git curl unzip

# Установка Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Установка Node.js 18+
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

**Для CentOS/RHEL:**
```bash
# Обновление системы
sudo yum update -y

# Установка EPEL репозитория
sudo yum install -y epel-release

# Установка необходимых пакетов
sudo yum install -y php-fpm php-mysql php-mbstring php-xml \
    php-curl php-zip php-gd php-bcmath nginx mysql-server git curl

# Установка Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Установка Node.js
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs
```

#### Шаг 2: Клонирование проекта

```bash
cd /var/www
sudo git clone <ваш-репозиторий> Virtus-prom
cd Virtus-prom
sudo chown -R $USER:$USER /var/www/Virtus-prom
```

#### Шаг 3: Установка зависимостей

```bash
# PHP зависимости
composer install --optimize-autoloader --no-dev

# Node.js зависимости
npm install

# Сборка фронтенда
npm run build
```

#### Шаг 4: Настройка базы данных

**Для MySQL:**
```bash
# Вход в MySQL
sudo mysql -u root -p

# Создание базы данных
CREATE DATABASE virtus_prom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'virtus_user'@'localhost' IDENTIFIED BY 'ваш_надежный_пароль';
GRANT ALL PRIVILEGES ON virtus_prom.* TO 'virtus_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Для SQLite (проще, но менее производительно):**
```bash
touch database/database.sqlite
chmod 664 database/database.sqlite
```

#### Шаг 5: Настройка .env файла

```bash
cp .env.example .env
nano .env  # или используйте vi/vim
```

**Минимальная конфигурация для продакшн:**
```env
APP_NAME="Virtus Prom"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Для MySQL:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=virtus_prom
DB_USERNAME=virtus_user
DB_PASSWORD=ваш_надежный_пароль

# Или для SQLite:
# DB_CONNECTION=sqlite
# DB_DATABASE=/var/www/Virtus-prom/database/database.sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_LEVEL=error
```

```bash
# Генерация ключа приложения
php artisan key:generate
```

#### Шаг 6: Запуск миграций

```bash
php artisan migrate --force
```

#### Шаг 7: Настройка прав доступа

```bash
# Создание необходимых директорий
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Установка прав
sudo chown -R www-data:www-data /var/www/Virtus-prom
sudo chmod -R 755 /var/www/Virtus-prom
sudo chmod -R 775 /var/www/Virtus-prom/storage
sudo chmod -R 775 /var/www/Virtus-prom/bootstrap/cache
```

#### Шаг 8: Настройка веб-сервера

##### Для Nginx:

Создайте файл `/etc/nginx/sites-available/virtus-prom`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/Virtus-prom/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    # Максимальный размер загружаемых файлов
    client_max_body_size 20M;

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
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Активируйте конфигурацию:
```bash
sudo ln -s /etc/nginx/sites-available/virtus-prom /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default  # Удалите дефолтный сайт, если не нужен
sudo nginx -t  # Проверка конфигурации
sudo systemctl reload nginx
```

##### Для Apache:

Создайте файл `/etc/apache2/sites-available/virtus-prom.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/Virtus-prom/public

    <Directory /var/www/Virtus-prom/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/virtus-prom-error.log
    CustomLog ${APACHE_LOG_DIR}/virtus-prom-access.log combined
</VirtualHost>
```

Активируйте конфигурацию:
```bash
sudo a2ensite virtus-prom.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

#### Шаг 9: Настройка SSL (Let's Encrypt)

```bash
# Установка Certbot
sudo apt install certbot python3-certbot-nginx  # для Nginx
# или
sudo apt install certbot python3-certbot-apache  # для Apache

# Получение сертификата
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
# или
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Автоматическое обновление (уже настроено в cron)
```

#### Шаг 10: Оптимизация для продакшн

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize
```

#### Шаг 11: Настройка очередей (если используются)

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

#### Шаг 12: Настройка cron для планировщика задач

```bash
sudo crontab -e -u www-data
```

Добавьте строку:
```
* * * * * cd /var/www/Virtus-prom && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔄 Обновление приложения

После внесения изменений в код:

```bash
cd /var/www/Virtus-prom

# Получение обновлений
git pull

# Обновление зависимостей
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Запуск миграций (если есть новые)
php artisan migrate --force

# Очистка и пересоздание кэша
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Или используйте скрипт обновления (см. `update-cloud.sh`)

---

## 🛠️ Полезные команды

### Проверка статуса сервисов
```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
```

### Просмотр логов
```bash
# Логи приложения
tail -f /var/www/Virtus-prom/storage/logs/laravel.log

# Логи Nginx
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Логи PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log
```

### Очистка кэша
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Перезапуск сервисов
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

---

## 🔒 Безопасность

1. **Настройте файрвол:**
   ```bash
   sudo ufw allow 22    # SSH
   sudo ufw allow 80    # HTTP
   sudo ufw allow 443   # HTTPS
   sudo ufw enable
   ```

2. **Отключите отображение ошибок в продакшн:**
   - Убедитесь, что `APP_DEBUG=false` в `.env`

3. **Используйте сильные пароли** для базы данных

4. **Регулярно обновляйте систему:**
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

5. **Настройте резервное копирование базы данных:**
   ```bash
   # Добавьте в cron для ежедневного бэкапа
   0 2 * * * mysqldump -u virtus_user -p'пароль' virtus_prom > /backup/virtus_prom_$(date +\%Y\%m\%d).sql
   ```

---

## ❗ Решение проблем

### Ошибка 500 Internal Server Error
1. Проверьте логи: `tail -f storage/logs/laravel.log`
2. Проверьте права: `ls -la storage/ bootstrap/cache/`
3. Проверьте `.env` файл
4. Очистите кэш: `php artisan config:clear`

### Ошибка "No application encryption key"
```bash
php artisan key:generate
```

### Ошибка подключения к базе данных
- Проверьте настройки в `.env`
- Убедитесь, что MySQL запущен: `sudo systemctl status mysql`
- Проверьте права пользователя БД

### Статические файлы не загружаются
- Убедитесь, что выполнили `npm run build`
- Проверьте, что `public/build/` существует
- Очистите кэш: `php artisan view:clear`

### Ошибка "Permission denied"
```bash
sudo chown -R www-data:www-data /var/www/Virtus-prom
sudo chmod -R 775 /var/www/Virtus-prom/storage
sudo chmod -R 775 /var/www/Virtus-prom/bootstrap/cache
```

---

## 📞 Поддержка

Если возникли проблемы:
1. Проверьте логи приложения
2. Проверьте логи веб-сервера
3. Убедитесь, что все сервисы запущены
4. Проверьте права доступа к файлам
