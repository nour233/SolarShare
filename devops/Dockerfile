FROM php:8.3-cli-alpine
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN apk add --no-cache unzip
RUN docker-php-ext-install pdo_mysql
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY artisan ./
COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/migrations/ database/migrations/
COPY database/seeders/ database/seeders/
COPY public/ public/
COPY resources/ resources/
COPY routes/ routes/

RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache \
    && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-dev --optimize
CMD ["php", "-v"]