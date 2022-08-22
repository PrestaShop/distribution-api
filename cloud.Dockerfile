FROM composer:2 as builder

COPY . /app/

RUN composer install --no-dev -o

FROM php:8.0-apache

RUN apt-get update && apt-get install -y libzip-dev && docker-php-ext-install zip

WORKDIR /var/www/html

COPY --from=builder /app/ /var/www/html

RUN echo "<?php echo 'Healthcheck';" > /var/www/html/index.php
RUN echo "<?php \$output = shell_exec('bin/console run'); echo '<pre>'.\$output.'</pre>';" > /var/www/html/run.php

RUN chown -R www-data:www-data /var/www

ARG TOKEN
ARG GOOGLE_APPLICATION_CREDENTIALS
ARG BUCKET_NAME
ARG PUBLIC_ASSETS_BASE_URL

EXPOSE 80