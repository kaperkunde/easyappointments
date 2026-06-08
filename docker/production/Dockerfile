FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

FROM node:20-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json gulpfile.js ./

RUN npm ci

COPY assets ./assets

RUN npx gulp compile

FROM php:8.2-apache

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

EXPOSE 80

WORKDIR /var/www/html

COPY docker/production/99-overrides.ini /usr/local/etc/php/conf.d/
COPY docker/production/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

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
