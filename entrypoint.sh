#!/bin/bash

set -e

echo "Running production setup commands..."

# Run database migrations
php artisan migrate --force || true

echo "Caching configuration and routes..."
php artisan config:cache
php artisan route:cache

# Start the default CMD of the base image (nginx + php-fpm)
exec /start.sh
