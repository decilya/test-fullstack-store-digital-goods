FROM php:8.3-fpm-bookworm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libsqlite3-dev sqlite3 \
    && docker-php-ext-install pdo_mysql pdo_sqlite zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Кастомный php.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html
EXPOSE 9000
CMD ["php-fpm"]