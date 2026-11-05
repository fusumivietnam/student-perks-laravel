# Portable Laravel image: same PHP-FPM runtime in development and production.
FROM node:26-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.* ./
COPY resources ./resources
RUN npm run build

FROM php:8.5-fpm-bookworm AS app
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libicu-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql intl zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader
COPY . .
COPY --from=frontend /app/public/build ./public/build
RUN composer dump-autoload --no-dev --classmap-authoritative && \
    php artisan filament:assets --no-interaction && \
    mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    php -v
# Preserve public release assets outside the named public volume mounted at runtime.
RUN mkdir -p /opt/app-public && cp -a public/. /opt/app-public/
COPY docker/app-entrypoint.sh /usr/local/bin/app-entrypoint.sh
EXPOSE 9000
ENTRYPOINT ["sh", "/usr/local/bin/app-entrypoint.sh"]
CMD ["php-fpm"]
