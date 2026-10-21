FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json gulpfile.js ./

RUN npm ci

COPY assets ./assets

RUN npx gulp compile

# Pinned to a patch release so a rebuild cannot reuse a cached, vulnerable base image. 8.2.35 is the first 8.2
# release that fixes the PHP2Shell remote code execution flaws (CVE-2026-109618, CVE-2026-109619).
FROM php:8.2.35-apache

# Fail the build when the base image runs a PHP release without the PHP2Shell fix (8.2.35, 8.3.36, 8.4.27, 8.5.12).
RUN php -r '$fixed = [80200 => 80235, 80300 => 80336, 80400 => 80427, 80500 => 80512]; \
        $branch = intdiv(PHP_VERSION_ID, 100) * 100; \
        exit($branch > 80500 || PHP_VERSION_ID >= ($fixed[$branch] ?? PHP_INT_MAX) ? 0 : 1);' \
    || (echo "PHP $(php -r 'echo PHP_VERSION;') is vulnerable to CVE-2026-109618/CVE-2026-109619." >&2 && exit 1)

ENV BASE_URL="http://localhost"
ENV LANGUAGE="english"
ENV DEBUG_MODE="FALSE"
ENV DB_HOST="mysql"
ENV DB_NAME="easyappointments"
ENV DB_USERNAME="root"
ENV DB_PASSWORD="secret"
ENV GOOGLE_SYNC_FEATURE=FALSE
ENV GOOGLE_CLIENT_ID=""
ENV GOOGLE_CLIENT_SECRET=""
ENV MAIL_PROTOCOL="smtp"
ENV MAIL_SMTP_DEBUG="0"
ENV MAIL_SMTP_AUTH="1"
ENV MAIL_SMTP_HOST=""
ENV MAIL_SMTP_USER=""
ENV MAIL_SMTP_PASS=""
ENV MAIL_SMTP_CRYPTO="tls"
ENV MAIL_SMTP_PORT="587"
ENV MAIL_FROM_ADDRESS=""
ENV MAIL_FROM_NAME=""
ENV MAIL_REPLY_TO_ADDRESS=""
ENV EMBED_ALLOWED_ORIGINS=""

EXPOSE 80

WORKDIR /var/www/html

COPY docker/production/99-overrides.ini /usr/local/etc/php/conf.d/
COPY docker/production/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/production/check-db-installed.php /usr/local/bin/check-db-installed.php
COPY docker/production/migrate-if-installed.sh /usr/local/bin/migrate-if-installed.sh

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        unzip \
        wget \
    && curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
        -o /usr/local/bin/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        curl gd intl ldap mbstring mysqli odbc pdo pdo_mysql xml zip exif gettext bcmath csv event imap inotify redis \
    && a2enmod rewrite \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/check-db-installed.php \
    && chmod +x /usr/local/bin/migrate-if-installed.sh \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY application ./application
COPY system ./system
COPY index.php config-sample.php openapi.yml ./
COPY storage ./storage

COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/assets ./assets

RUN chown -R www-data:www-data .

ENTRYPOINT ["docker-entrypoint.sh"]
