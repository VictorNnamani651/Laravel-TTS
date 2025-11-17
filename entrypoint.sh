#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

echo "Running production setup commands..."

# Run database migrations
php artisan migrate --force

echo "Caching configuration and routes..."
php artisan config:cache
php artisan route:cache

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf