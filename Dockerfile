FROM php:8.2-cli-alpine

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy all your KnProxy repository codebase files into the container
COPY . .

# Expose standard web traffic port
EXPOSE 80

# Start PHP's built-in, lightweight web server on port 80
CMD ["php", "-S", "0.0.0.0:80"]
