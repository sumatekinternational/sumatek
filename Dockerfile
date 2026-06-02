# syntax=docker/dockerfile:1
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        git zip unzip icu-dev oniguruma-dev postgresql-dev libpng-dev linux-headers \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql intl mbstring gd bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist || true

COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
