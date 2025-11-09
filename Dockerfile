FROM php:7.4-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite

COPY ./www /var/www/html/

WORKDIR /var/www/html

RUN mkdir -p /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/uploads && \
    chmod 755 /var/www/html/uploads

CMD apache2-foreground
