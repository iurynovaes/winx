FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        zlib1g-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN printf "upload_max_filesize=20M\npost_max_size=20M\nmemory_limit=512M\n" \
    > /usr/local/etc/php/conf.d/app.ini
