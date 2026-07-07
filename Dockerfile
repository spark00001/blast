FROM webdevops/php-apache:8.2

# Copy all your KnProxy repository codebase files into the server webroot
COPY . /app/

# Expose standard web traffic port
EXPOSE 80
