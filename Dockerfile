FROM php:8.5-cli

RUN apt-get update && apt-get install -y \
    $PHPIZE_DEPS \
    libcurl4-openssl-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    git \
    && docker-php-ext-install pcntl pdo_mysql zip \
    && pecl install redis raphf \
    && docker-php-ext-enable redis raphf \
    && pecl install pecl_http \
    && docker-php-ext-enable http \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/parser

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

COPY . .
RUN composer dump-autoload --optimize