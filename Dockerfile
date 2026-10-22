FROM composer:2 as builder

COPY . /app/

RUN composer install --no-dev -o

FROM php:8.4-alpine

# Production php.ini: hides deprecation notices emitted by vendor libraries (e.g. google/cloud-core on PHP 8.4)
# and does not display errors in the output (they are logged to stderr instead)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && apk add --no-cache libzip-dev && docker-php-ext-install zip

COPY --from=builder /app/ /app

ARG TOKEN
ARG GOOGLE_APPLICATION_CREDENTIALS
ARG BUCKET_NAME
ARG PUBLIC_ASSETS_BASE_URL

WORKDIR /app

ENTRYPOINT [ "./bin/console" ]