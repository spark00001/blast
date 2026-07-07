FROM php:8.2-apache

# Copy all your KnProxy repository codebase files into the server webroot
COPY . /var/www/html/

# Expose standard web traffic port
EXPOSE 80
