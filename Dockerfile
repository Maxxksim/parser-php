FROM php:8.5-cli

RUN apt-get update && apt-get install -y \
    docker-php-ext-install pcntl \
    && pecl install redis raphf pecl_http \
    && docker-php-ext-enable redis raphf http \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/parser

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

COPY . .
RUN composer dump-autoload --optimize