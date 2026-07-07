FROM php:8.2-fpm-alpine

# Install Nginx to handle public web traffic
RUN apk add --no-cache nginx

# Copy custom Nginx routing rules directly into the container
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

# Copy all your KnProxy code repository files into the server directory
COPY . /var/www/html/

# Expose standard web traffic port
EXPOSE 80

# Start both PHP backend and Nginx web server simultaneously 
CMD php-fpm -D && nginx -g "daemon off;"
