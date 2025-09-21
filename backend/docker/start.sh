#!/usr/bin/env bash

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting NGINX..."
nginx

echo "Running Laravel setup..."

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Cache configuration
echo "Caching config..."
php artisan config:cache

# Cache routes
echo "Caching routes..."
php artisan route:cache

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear and cache views
echo "Caching views..."
php artisan view:cache

echo "Laravel application is ready!"

# Keep the container running
tail -f /dev/null
