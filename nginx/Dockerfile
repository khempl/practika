FROM nginx:alpine

# копируем тот же исходный код, что и в php-контейнер - nginx использует его
# только чтобы отдавать статику (css) и знать, какие файлы вообще существуют.
# сам php-код исполняет php-fpm контейнер, а не nginx.
COPY php/src /var/www/html
COPY nginx/default.conf /etc/nginx/conf.d/default.conf
