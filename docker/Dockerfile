# Estágio 1: build (PHP + Node — Wayfinder exige php artisan durante npm run build)
FROM php:8.3-cli-bookworm AS builder

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl libpq-dev libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo_pgsql pgsql mbstring zip gd opcache \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

ENV APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    APP_ENV=production \
    APP_DEBUG=false

RUN composer dump-autoload --optimize \
    && php artisan wayfinder:generate --with-form --no-interaction \
    && npm run build

# Estágio 2: aplicação PHP (runtime)
FROM php:8.3-fpm-bookworm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo_pgsql pgsql mbstring zip gd opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=builder /app /var/www/html

RUN rm -rf node_modules \
    && cp -a public /opt/public-seed \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php-fpm/99-app-env.conf /usr/local/etc/php-fpm.d/99-app-env.conf
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
