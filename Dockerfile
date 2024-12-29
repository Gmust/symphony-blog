# syntax=docker/dockerfile:1

# Base FrankenPHP image
FROM dunglas/frankenphp:1-php8.3 AS frankenphp_upstream
FROM frankenphp_upstream AS frankenphp_base

WORKDIR /app
VOLUME /app/var/

# persistent / runtime deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    acl \
    file \
    gettext \
    git \
    libpq-dev \
    default-libmysqlclient-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN set -eux; \
    install-php-extensions \
        @composer \
        apcu \
        intl \
        opcache \
        zip \
        pdo_pgsql \
        pdo_mysql \
        http

# Environment settings
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

# **Create session save path directory**
RUN mkdir -p /tmp/sessions && chmod -R 733 /tmp/sessions && chown -R www-data:www-data /tmp/sessions

# Copy necessary configurations
COPY --link frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link frankenphp/Caddyfile /etc/caddy/Caddyfile

# **Copy custom PHP configuration**
COPY --link frankenphp/conf.d/custom.ini $PHP_INI_DIR/app.conf.d/

ENTRYPOINT ["docker-entrypoint"]
HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1
CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]

# Dev FrankenPHP image
FROM frankenphp_base AS frankenphp_dev
ENV APP_ENV=dev XDEBUG_MODE=off

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN set -eux; \
    install-php-extensions \
        xdebug;

COPY --link frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch" ]

# Prod FrankenPHP image
FROM frankenphp_base AS frankenphp_prod
ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/
COPY --link frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile

# Prevent the reinstallation of vendors at every change in the source code
COPY --link composer.* symfony.* ./
RUN set -eux; \
    composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# Copy sources
COPY --link . ./
RUN rm -Rf frankenphp/

# Final production preparations
RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    composer dump-env prod; \
    composer run-script --no-dev post-install-cmd; \
    chmod +x bin/console; sync;
