FROM node:24-bookworm-slim AS node-runtime
FROM composer:2 AS composer-runtime

FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install --no-install-recommends --yes $PHPIZE_DEPS git libpq-dev unzip \
    && docker-php-ext-install dom pcntl pdo_pgsql posix \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer-runtime /usr/bin/composer /usr/bin/composer
COPY --from=node-runtime /usr/local/ /usr/local/

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist

COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000 5173

STOPSIGNAL SIGTERM

CMD ["composer", "run", "dev:container"]
