FROM php:8.3-zts

# Dev‑пакеты + зависимости для PECL и libzip
RUN apt-get update && apt-get install -y \
    build-essential autoconf pkg-config re2c \
    libonig-dev libcurl4-openssl-dev libxml2-dev libzip-dev \
    zip unzip git curl \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN git config --global --add safe.directory /app

# ext‑parallel (поддерживается PHP 8 ZTS)
RUN pecl install parallel \
 && docker-php-ext-enable parallel \
    && pecl install pcov \
    && docker-php-ext-enable pcov

# Стандартные расширения, нужные PHPUnit
RUN docker-php-ext-install mbstring curl xml zip

WORKDIR /app