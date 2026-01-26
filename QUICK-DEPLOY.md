# 🚀 Быстрый деплой на облачный сервер

> 🌐 **Хотите сделать сайт доступным из интернета?** См. [PUBLISH-ONLINE.md](./PUBLISH-ONLINE.md)

## Минимальные шаги для запуска

### 1. Подключитесь к серверу
```bash
ssh user@your-server-ip
```

### 2. Установите зависимости (один раз)
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-zip nginx mysql-server git curl
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash - && sudo apt install -y nodejs
```

### 3. Клонируйте проект
```bash
cd /var/www
sudo git clone <ваш-репозиторий> Virtus-prom
cd Virtus-prom
sudo chown -R $USER:$USER /var/www/Virtus-prom
```

### 4. Запустите автоматический деплой
```bash
chmod +x deploy-cloud.sh
./deploy-cloud.sh
```

### 5. Настройте базу данных
```bash
# Для MySQL:
sudo mysql -u root -p
CREATE DATABASE virtus_prom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'virtus_user'@'localhost' IDENTIFIED BY 'пароль';
GRANT ALL PRIVILEGES ON virtus_prom.* TO 'virtus_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Отредактируйте .env файл:
nano .env
# Установите DB_CONNECTION=mysql и данные БД
```

### 6. Настройте Nginx
```bash
sudo nano /etc/nginx/sites-available/virtus-prom
```

Вставьте конфигурацию (замените `yourdomain.com` на ваш домен):
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/Virtus-prom/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Активируйте:
```bash
sudo ln -s /etc/nginx/sites-available/virtus-prom /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. Настройте SSL (опционально, но рекомендуется)
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 8. Готово! 🎉
Откройте ваш домен в браузере.

---

## Обновление приложения

```bash
cd /var/www/Virtus-prom
chmod +x update-cloud.sh
./update-cloud.sh
```

---

## Если что-то пошло не так

1. **Проверьте логи:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Проверьте права:**
   ```bash
   sudo chown -R www-data:www-data /var/www/Virtus-prom
   sudo chmod -R 775 storage bootstrap/cache
   ```

3. **Очистите кэш:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

Полная инструкция: [DEPLOY-CLOUD.md](./DEPLOY-CLOUD.md)
