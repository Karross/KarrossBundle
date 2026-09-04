FROM php:8.5-cli

# System dependencies (ICU for ext-intl, git for composer/GitHub deps, unzip/curl for composer)
RUN     apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libicu-dev \
        libzip-dev \
        libsqlite3-dev \
    && docker-php-ext-install \
        intl \
        pdo \
        pdo_sqlite \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
