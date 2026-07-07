FROM php:8.2-fpm-alpine

# Install Nginx to serve web pages
RUN apk add --no-cache nginx

# Copy modern Nginx routing configuration directly into the container
RUN printf '%s\n' \
    'server {' \
    '    listen 80;' \
    '    root /var/www/html;' \
    '    index index.php index.html;' \
    '    location / {' \
    '        try_files $uri $uri/ /index.php?$args;' \
    '    }' \
    '    location ~ \.php$ {' \
    '        include fastcgi_params;' \
    '        fastcgi_pass 127.0.0.1:9000;' \
    '        fastcgi_index index.php;' \
    '        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;' \
    '    }' \
    '}' > /etc/nginx/http.d/default.conf

# Copy all your KnProxy repository codebase files into the container webroot
COPY . /var/www/html/

# Expose standard web traffic port
EXPOSE 80

# Clean boot manager script to keep both services running smoothly in the foreground
CMD sh -c "php-fpm & nginx -g 'daemon off;'"
