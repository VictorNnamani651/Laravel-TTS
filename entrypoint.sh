#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

echo "Running production setup commands..."

# Run database migrations
php artisan migrate --force

echo "Caching configuration and routes..."
php artisan config:cache
php artisan route:cache

# Start the web server (The original Docker CMD)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf