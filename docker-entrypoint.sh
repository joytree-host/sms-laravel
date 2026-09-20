#!/bin/sh
set -e

# Forgiving DB config: if DB_CONNECTION was set to a full connection URL
# (e.g. mysql://user:pass@host:port/db) instead of just the driver name,
# Laravel fails with "Database connection [mysql://...] not configured".
# Treat that value as DB_URL and reduce DB_CONNECTION to its driver name.
# (Nothing is printed, so credentials never reach the logs.)
case "$DB_CONNECTION" in
    *://*)
        export DB_URL="${DB_URL:-$DB_CONNECTION}"
        export DB_CONNECTION="${DB_CONNECTION%%://*}"
        ;;
esac

# Generate APP_KEY only if one isn't already set (first boot convenience;
# a real deployment should set APP_KEY as a fixed environment variable so
# it never changes between deploys/restarts, which would invalidate all
# existing sessions and encrypted data).
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Cache config/routes/views for production performance.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Apply any pending migrations. Safe to run on every boot: Laravel tracks
# which migrations have already run and skips them.
php artisan migrate --force

# Make the public/storage symlink for profile-photo uploads if it doesn't
# already exist (see config/filesystems.php's 'public' disk).
php artisan storage:link || true

exec "$@"
