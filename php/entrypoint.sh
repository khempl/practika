#!/bin/sh
set -e

# Создание папок
mkdir -p /var/www/html/errors /var/www/html/uploads /var/www/html/jobs

# Смена владельца на www-data (пользователь PHP-FPM)
chown -R www-data:www-data /var/www/html/errors /var/www/html/uploads /var/www/html/jobs

# Права 755
chmod -R 755 /var/www/html/errors /var/www/html/uploads /var/www/html/jobs

exec "$@"
