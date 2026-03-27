# PHP-FPM for TrainLog API (Symfony)
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    icu-dev \
    libpq-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl pdo_pgsql opcache

RUN apk add --no-cache git unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist 2>/dev/null || true
COPY . .
RUN composer install --no-dev --no-scripts --prefer-dist 2>/dev/null || composer install --no-scripts --prefer-dist \
    && composer dump-autoload --optimize --no-dev

RUN if [ -f /app/.env.example ]; then cp /app/.env.example /app/.env; else printf "APP_ENV=prod\nAPP_DEBUG=0\n" > /app/.env; fi \
    && mkdir -p /app/var/cache /app/var/log \
    && APP_ENV=prod APP_DEBUG=0 php bin/console assets:install public --env=prod \
    && cp -a /app/public /app/public_origin \
    && chown -R www-data:www-data /app/var

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-trainlog.ini
COPY docker/php/zzz-env.conf /usr/local/etc/php-fpm.d/zzz-env.conf
COPY docker/entrypoint.sh /app/docker/entrypoint.sh
RUN chmod +x /app/docker/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["php-fpm"]
