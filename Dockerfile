#VERSION 1.0.0
FROM keboola/base-php56
MAINTAINER Miro Cillik <miro@keboola.com>

# Install dependencies
#RUN yum -y --enablerepo=epel,remi,remi-php56 install php-devel

ADD . /code
WORKDIR /code
RUN echo "memory_limit = -1" >> /etc/php.ini
RUN composer install --no-interaction

ENTRYPOINT php ./vendor/bin/phpunit
