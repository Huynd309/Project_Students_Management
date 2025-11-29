# Sử dụng PHP 8.2 với Apache
FROM php:8.2-apache

# Cài đặt driver PostgreSQL cho PHP 
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Bật mod_rewrite của Apache 
RUN a2enmod rewrite

# Copy toàn bộ code vào thư mục web của server
COPY . /var/www/html/

# Cấp quyền cho thư mục
RUN chown -R www-data:www-data /var/www/html

# Mở cổng 80
EXPOSE 80