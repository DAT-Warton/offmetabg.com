FROM php:8.1-apache

WORKDIR /var/www/html

COPY . .

RUN a2enmod rewrite

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000"]
