FROM php:8.2-fpm-alpine

# 1. Install Nginx to serve pages
RUN apk add --no-cache nginx

# 2. Copy the custom Nginx server routing configurations directly
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

# 3. Copy all your KnProxy repository code files into the container webroot
COPY . /var/www/html/

# 4. Create an internal background starter script to boot both engines safely
RUN printf '%s\n' \
    '#!/bin/sh' \
    'php-fpm -D' \
    'exec nginx -g "daemon off;"' > /entrypoint.sh && chmod +x /entrypoint.sh

# 5. Expose standard web traffic port
EXPOSE 80

# 6. Execute our new background starter script on boot
ENTRYPOINT ["/entrypoint.sh"]
