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

# Node.js 20 (requis par playwright-php) via NodeSource
RUN     curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Libraries needed by headless Chromium (Playwright)
RUN     apt-get update && apt-get install -y --no-install-recommends \
        libnss3 \
        libnspr4 \
        libatk1.0-0 \
        libatk-bridge2.0-0 \
        libcups2 \
        libdrm2 \
        libxkbcommon0 \
        libatspi2.0-0 \
        libxcomposite1 \
        libxdamage1 \
        libxfixes3 \
        libxrandr2 \
        libgbm1 \
        libpango-1.0-0 \
        libcairo2 \
        libasound2 \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Playwright browsers are installed in a project-persistent directory
# (persisted on the host via the mounted volume), see Makefile (e2e-browsers).
ENV PLAYWRIGHT_BROWSERS_PATH=/app/var/ms-playwright

WORKDIR /app
