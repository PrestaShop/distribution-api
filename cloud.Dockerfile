FROM composer:2 as builder

COPY . /app/

RUN composer install --no-dev -o

FROM php:8.1-apache

WORKDIR /var/www/html

COPY --from=builder /app/ /var/www/html

RUN echo "<?php echo 'Healthcheck';" > /var/www/html/index.php
RUN echo "<?php shell_exec('bin/console run');" > /var/www/html/run.php

RUN chown -R www-data:www-data /var/www

ARG TOKEN
ARG GOOGLE_APPLICATION_CREDENTIALS
ARG BUCKET_NAME

EXPOSE 80