FROM php:8.2-apache

# 1. Enable standard Apache URL rewriting capabilities for the proxy configurations
RUN a2enmod rewrite

# 2. Modify configuration parameters so local routing overwrites are explicitly permitted
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# 3. Securely transfer your KnProxy project framework files straight to the main default folder
COPY . /var/www/html/

# 4. Announce default production port variables naturally
EXPOSE 80