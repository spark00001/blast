FROM php:8.2-apache

# Enable Apache Mod_Rewrite for proxy URL manipulation
RUN a2enmod rewrite

# Copy all repository files to the Apache public HTML directory
COPY . /var/www/html/

# Expose standard web traffic port
EXPOSE 80
