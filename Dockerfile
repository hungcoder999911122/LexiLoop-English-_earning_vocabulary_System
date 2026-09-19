FROM php:8.2-apache

# Cài đặt extension mysqli và pdo_mysql
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Kích hoạt module mod_rewrite cho Apache (nếu cần dùng router/URL rewrite)
RUN a2enmod rewrite