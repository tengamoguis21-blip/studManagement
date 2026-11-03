FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm
USER root
RUN rm -f /etc/apt/sources.list.d/yarn.list /etc/apt/sources.list.d/yarn.sources \
    && apt-get update && apt-get install -y --no-install-recommends libpq-dev libzip-dev libicu-dev postgresql-client \
    && docker-php-ext-install pdo_pgsql pgsql bcmath intl zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /workspaces/studManagement
