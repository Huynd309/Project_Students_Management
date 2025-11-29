FROM php:8.2-apache

# Cài đặt driver PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Cấu hình PHP để đọc biến môi trường vào $_ENV
RUN echo "variables_order = \"EGPCS\"" > /usr/local/etc/php/conf.d/variables_order.ini

# Bật mod_rewrite
RUN a2enmod rewrite

# Copy code
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80