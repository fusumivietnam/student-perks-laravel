# Shared production image for local development and VPS deployment.
FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.* ./
COPY resources ./resources
RUN npm run build

FROM dunglas/frankenphp:1-php8.3
WORKDIR /app
RUN install-php-extensions pdo_pgsql intl zip opcache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --no-scripts --optimize-autoloader
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --no-dev --classmap-authoritative && \
    mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache
ENV APP_ENV=production
ENV APP_DEBUG=false
EXPOSE 80 443
