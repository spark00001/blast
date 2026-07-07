FROM php:8.2-cli-alpine

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy all your KnProxy repository codebase files into the container
COPY . .

# Explicitly expose port 8080 as a fallback reference marker
EXPOSE 8080

# Explicitly invoke an executable shell to properly pass the Railway dynamic $PORT
CMD ["/bin/sh", "-c", "php -S 0.0.0.0:${PORT}"]
