# 1. Use the base image
FROM richarvey/nginx-php-fpm:latest

# 2. Copy application code to the container
# We copy to /var/www/html which is the default for this image
COPY . /var/www/html

# 3. Install Composer dependencies
# This is the missing step that caused your error!
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 4. Fix configurations & permissions
# Ensure the web server owns the files (crucial for storage & cache)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# 5. Image Environment Variables
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

# 6. Setup the Entrypoint Script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 7. Run the script on start
CMD ["/usr/local/bin/entrypoint.sh"]