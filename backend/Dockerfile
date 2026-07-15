# syntax=docker/dockerfile:1.7

FROM php:8.3-cli-bookworm AS vendor
WORKDIR /app
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd mbstring pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY backend/composer.json ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --classmap-authoritative
COPY backend ./
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

FROM php:8.3-fpm-bookworm AS php-fpm
ARG APP_ENV=production
ENV APP_ENV=${APP_ENV} \
    PHP_FPM_MAX_CHILDREN=20 \
    PHP_FPM_START_SERVERS=4 \
    PHP_FPM_MIN_SPARE_SERVERS=2 \
    PHP_FPM_MAX_SPARE_SERVERS=8 \
    PHP_FPM_MAX_REQUESTS=500
WORKDIR /var/www/backend
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        default-mysql-client \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd mbstring opcache pcntl pdo_mysql zip \
    && apt-get purge -y --auto-remove libfreetype6-dev libjpeg62-turbo-dev libonig-dev libpng-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*
COPY docker/php/php.ini /usr/local/etc/php/conf.d/skyfi.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY --from=vendor --chown=www-data:www-data /app /var/www/backend
RUN mkdir -p storage/logs storage/cache storage/uploads \
    && chown -R www-data:www-data storage \
    && chmod -R ug+rwX storage
EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 CMD php -r 'exit(is_file("/var/www/backend/public/index.php") ? 0 : 1);'
CMD ["php-fpm", "-F"]

FROM php-fpm AS development
COPY docker/php/php.dev.ini /usr/local/etc/php/conf.d/zz-skyfi-dev.ini
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

FROM php-fpm AS supervisor
USER root
RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/log/supervisor /var/run/supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/scripts/skyfi-worker.sh /usr/local/bin/skyfi-worker
RUN chmod +x /usr/local/bin/skyfi-worker \
    && chown -R www-data:www-data /var/log/supervisor /var/run/supervisor
USER www-data
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 CMD supervisorctl -c /etc/supervisor/supervisord.conf status skyfi-worker | grep -q RUNNING
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
