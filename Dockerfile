FROM php:8.3-zts

# 1) Системные зависимости и dev-пакеты, нужные для PECL, ZIP и т.д.
RUN apt-get update && apt-get install -y \
    build-essential autoconf pkg-config re2c \
    libonig-dev libcurl4-openssl-dev libxml2-dev libzip-dev \
    zip unzip git curl \
  && rm -rf /var/lib/apt/lists/*

# 2) Устанавливаем Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN git config --global --add safe.directory /app

# 3) Устанавливаем ext-parallel
RUN pecl install parallel \
  && docker-php-ext-enable parallel

WORKDIR /app

# 4) Копируем только composer-файлы и устанавливаем зависимости
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-progress --no-interaction --optimize-autoloader

# 5) Копируем весь остальной код
COPY . .

# 6) По умолчанию прогоняем тесты
CMD ["vendor/bin/phpunit", "--colors=always"]
