FROM composer:2 as builder

COPY . /app/

RUN composer install

FROM php:8.1

COPY --from=builder /app/ /app
