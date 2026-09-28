# =====================================================================
# Stage 1: install PHP dependencies with Composer
# (Composer is only needed while building, so it stays out of the final image)
# =====================================================================
FROM composer:2 AS vendor
WORKDIR /app

# Copy only composer files first. Docker caches this layer, so dependencies
# are re-downloaded only when composer.json / composer.lock change.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
    --no-interaction --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# =====================================================================
# Stage 2: the image that actually runs in Kubernetes
# PHP + Apache in one container, listening on port 80
# =====================================================================
FROM php:8.4-apache

# PHP extensions: pdo_pgsql = talk to PostgreSQL (CNPG), opcache = speed
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/*

# Laravel's public/ folder is the web root. AllowOverride lets public/.htaccess
# send every request to index.php (needs mod_rewrite).
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel \
    && a2enmod rewrite

# Production PHP settings
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html

# Register installed packages, then let Apache's user write to storage/cache
RUN php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
