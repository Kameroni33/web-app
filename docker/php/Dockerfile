# syntax=docker/dockerfile:1
# Build context: ./backend; additional context "php-conf": ./docker/php

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# pcntl/posix/redis live in the runtime image, not the composer image.
RUN composer install --no-dev --no-interaction --prefer-dist \
      --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
# Runs package:discover, which rebuilds bootstrap/cache without dev-only providers.
RUN composer dump-autoload --no-dev --optimize --ignore-platform-reqs

FROM php:8.4-fpm-alpine
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql redis pcntl intl opcache gd \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
COPY --from=php-conf opcache.ini $PHP_INI_DIR/conf.d/zz-opcache.ini
COPY --from=php-conf uploads.ini $PHP_INI_DIR/conf.d/zz-uploads.ini

WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /app /var/www/html

USER www-data
# Default CMD from the base image is php-fpm, listening on :9000.
