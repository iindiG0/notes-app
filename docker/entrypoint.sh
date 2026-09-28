#!/bin/sh
# Runs every time the container starts, before Apache (or any other command).
set -e

# Cache config/routes/views using the environment variables Kubernetes
# passed in (DB_HOST, APP_KEY, ...). Makes every request faster.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Hand over to the real command: apache2-foreground by default,
# or "php artisan migrate --force" in the migration Job.
exec "$@"
