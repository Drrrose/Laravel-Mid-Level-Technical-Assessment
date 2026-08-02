# Frontend build stage
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts || npm install --ignore-scripts
COPY . .
RUN npm run build

# PHP dependencies stage
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-req=php
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-req=php

# Application image
FROM php:8.3-fpm-alpine
RUN apk add --no-cache $PHPIZE_DEPS linux-headers icu-dev \
    && docker-php-ext-install pdo_mysql intl opcache \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www

COPY --from=vendor /app /var/www
COPY --from=frontend /app/public/build /var/www/public/build

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
