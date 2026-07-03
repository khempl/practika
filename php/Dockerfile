FROM php:8.2-fpm

# install-php-extensions сам ставит нужные системные пакеты
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions mongodb mbstring zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json /var/www/html/composer.json
RUN composer install --no-interaction --no-dev --optimize-autoloader

COPY src/ /var/www/html/
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

RUN mkdir -p /var/www/html/errors /var/www/html/uploads /var/www/html/jobs \
    && chmod -R 777 /var/www/html/errors /var/www/html/uploads /var/www/html/jobs

EXPOSE 9000
