# syntax=docker/dockerfile:1.7
# The vendor stage uses the runtime PHP extensions required by Composer platform checks.


FROM dunglas/frankenphp:1-php8.5-alpine AS base

RUN install-php-extensions \
    pdo_pgsql \
    pdo_sqlite \
    pcntl \
    bcmath \
    sockets \
    opcache

WORKDIR /app


FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY services/billing-service/composer.json services/billing-service/composer.lock ./
COPY packages /packages

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY services/billing-service/ ./

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative


FROM base AS runtime

ENV APP_ENV=production \
    LOG_CHANNEL=stderr

COPY --from=vendor /app /app

RUN addgroup -g 1000 laravel \
    && adduser -D -u 1000 -G laravel laravel \
    && chown -R laravel:laravel /app/storage /app/bootstrap/cache /data /config

USER laravel

EXPOSE 8080

CMD ["frankenphp", "php-server", "--listen", ":8080", "--root", "/app/public"]
