FROM php:7.4-fpm-alpine

RUN apk add --no-cache openssl mysql-client nodejs npm bash alpine-sdk autoconf librdkafka-dev
RUN docker-php-ext-install pdo pdo_mysql bcmath

RUN pecl install rdkafka

RUN ln -s /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    echo "extension=rdkafka.so" >> /usr/local/etc/php/php.ini

WORKDIR /var/www

RUN rm -rf /var/www/html
RUN ln -s public html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

EXPOSE 9000

ENTRYPOINT [ "php-fpm" ]