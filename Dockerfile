FROM php:8.2-cli-alpine

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy all your KnProxy repository codebase files into the container
COPY . .

# Inform Docker that the application expects to use a variable port
EXPOSE ${PORT}

# Use Shell Form to allow runtime environment variable expansion
CMD php -S 0.0.0.0:${PORT:-8080}
