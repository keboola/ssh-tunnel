FROM php:8.3-cli-bookworm

ARG DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_PROCESS_TIMEOUT=3600

COPY composer-install.sh /tmp/composer-install.sh

RUN apt-get update -q \
  && apt-get install unzip git wget ssh -y --no-install-recommends \
  && rm -rf /var/lib/apt/lists/* \
  && /tmp/composer-install.sh \
  && rm /tmp/composer-install.sh \
  && mv composer.phar /usr/local/bin/composer

RUN docker-php-ext-install pdo_mysql

COPY . /code
WORKDIR /code

RUN echo "memory_limit = -1" >> /usr/local/etc/php/php.ini
RUN composer install --no-interaction

CMD php ./vendor/bin/phpunit
