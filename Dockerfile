FROM composer:2 as builder

COPY . /app/

RUN composer install --no-dev -o

FROM php:8.0-alpine

RUN apk add --no-cache libzip-dev && docker-php-ext-install zip

COPY --from=builder /app/ /app

ARG TOKEN
ARG GOOGLE_APPLICATION_CREDENTIALS
ARG BUCKET_NAME
ARG PUBLIC_ASSETS_BASE_URL

WORKDIR /app

ENTRYPOINT [ "./bin/console" ]