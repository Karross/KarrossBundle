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
        bcmath \
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

# AST Metrics (deterministic complexity/volume gate, `make qa` + CI): single
# static binary, downloaded during build and verified against a pinned SHA256.
ARG AST_METRICS_VERSION=v0.43.0
ARG AST_METRICS_SHA256=afebb971f6b16878e3a4e58b74e0e6dcf9a9161f34b54547a7799219df789161
RUN     curl -fsSL -o /usr/local/bin/ast-metrics \
        https://github.com/ast-metrics/ast-metrics/releases/download/${AST_METRICS_VERSION}/ast-metrics_Linux_x86_64 \
    && printf '%s  %s\n' "${AST_METRICS_SHA256}" /usr/local/bin/ast-metrics | sha256sum -c - \
    && chmod +x /usr/local/bin/ast-metrics

# Playwright browsers are installed in a project-persistent directory
# (persisted on the host via the mounted volume), see Makefile (e2e-browsers).
ENV PLAYWRIGHT_BROWSERS_PATH=/app/var/ms-playwright

WORKDIR /app
