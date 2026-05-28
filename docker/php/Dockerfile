FROM php:8.4-fpm

# Установка зависимостей для PHP и сборки расширений
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    zip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    autoconf \
    make \
    gcc \
    g++ \
    pkg-config \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        bcmath \
        mbstring \
        xml \
        pdo_pgsql \
        pgsql \
    # && pecl channel-update pecl.php.net \
    # && pecl install xdebug \
    # && docker-php-ext-enable xdebug \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www