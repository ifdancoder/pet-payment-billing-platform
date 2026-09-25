# syntax=docker/dockerfile:1.7

# Runtime: FrankenPHP (github.com/dunglas/frankenphp) — a single static
# binary bundling PHP and Caddy, one process per container instead of the
# traditional php-fpm + nginx pair. No Octane/worker-mode here yet
# (classic one-request-per-process, same request model as php-fpm) — that's
# a later optimization, not a requirement for this image to work.
# See docs/adr/0003-kubernetes-foundation.md for why.

FROM dunglas/frankenphp:1-php8.5-alpine AS base

RUN install-php-extensions \
    pdo_pgsql \
    pdo_sqlite \
    pcntl \
    bcmath \
    sockets \
    opcache

WORKDIR /app

# --- Dependencies -----------------------------------------------------

# Built FROM base, not the official composer:2 image — composer:2 doesn't
# have ext-sockets, and php-amqplib's platform check fails without it.
# Reusing base keeps one consistent extension set between the "does
# composer install even resolve" check and the actual runtime.
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY services/subscription-service/composer.json services/subscription-service/composer.lock ./
COPY packages /packages

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY services/subscription-service/ ./

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# --- Runtime ------------------------------------------------------------

FROM base AS runtime

ENV APP_ENV=production \
    LOG_CHANNEL=stderr

COPY --from=vendor /app /app

RUN addgroup -g 1000 laravel \
    && adduser -D -u 1000 -G laravel laravel \
    && chown -R laravel:laravel /app/storage /app/bootstrap/cache /data /config

USER laravel

EXPOSE 8080

# Classic mode — one PHP process per request, same request model as
# php-fpm, no persistent app state. No laravel/octane dependency needed;
# that's a later optimization once the classic-mode image is proven out.
CMD ["frankenphp", "php-server", "--listen", ":8080", "--root", "/app/public"]
