# Use a generic image with Nginx & PHP configured
# Check https://hub.docker.com/r/richarvey/nginx-php-fpm/tags for the version matching your PHP needs
FROM richarvey/nginx-php-fpm:latest

# Copy application code
COPY . .

# Image Configurations
ENV SKIP_COMPOSER 0
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel Configurations (can also be set in Render Dashboard)
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

# Allow Composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

# Copy the custom entrypoint script and make it executable
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Use the custom script as the main command
CMD ["entrypoint.sh"]