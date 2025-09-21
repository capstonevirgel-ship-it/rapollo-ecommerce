FROM nginx:alpine

# Install PHP and required extensions
RUN apk add --no-cache \
    php81 \
    php81-fpm \
    php81-pdo \
    php81-pdo_pgsql \
    php81-mbstring \
    php81-xml \
    php81-curl \
    php81-zip \
    php81-tokenizer \
    php81-fileinfo \
    php81-openssl \
    php81-json \
    php81-ctype \
    php81-bcmath \
    php81-gd \
    php81-intl \
    php81-xmlreader \
    php81-xmlwriter \
    php81-simplexml \
    php81-dom \
    php81-xml \
    php81-tokenizer \
    php81-fileinfo \
    php81-openssl \
    php81-json \
    php81-ctype \
    php81-bcmath \
    php81-gd \
    php81-intl \
    php81-xmlreader \
    php81-xmlwriter \
    php81-simplexml \
    php81-dom \
    composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy PHP-FPM configuration
COPY docker/php-fpm.conf /etc/php81/php-fpm.d/www.conf

# Copy startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Expose port
EXPOSE 80

# Start the application
CMD ["/start.sh"]
